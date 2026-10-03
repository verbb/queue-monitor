<?php
namespace verbb\queuemonitor\services;

use verbb\queuemonitor\QueueMonitor;
use verbb\queuemonitor\helpers\WebhookUrl;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\UrlHelper;

use yii\db\Expression;
use yii\queue\ExecEvent;

use RuntimeException;
use Throwable;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;

class Service extends Component
{
    // Constants
    // =========================================================================

    private const STALLED_QUEUE_ALERT_CACHE_KEY = 'queue-monitor:stalled-queue-alert';
    private const STALLED_QUEUE_ALERT_MUTEX_KEY = 'queue-monitor:stalled-queue-alert-lock';
    private const WEBHOOK_CONNECT_TIMEOUT = 5;
    private const WEBHOOK_TIMEOUT = 15;


    // Public Methods
    // =========================================================================

    public function sendFailedJobEmail(ExecEvent $event): void
    {
        $settings = QueueMonitor::$plugin->getSettings();

        // Only send on first attempt, else we just get hassled
        if ((int)$event->attempt !== 1 || !$settings->getSendFailedJobEmail()) {
            return;
        }

        $sentEmails = [];

        foreach ($settings->getFailedJobUsers() as $user) {
            Craft::$app->getMailer()
                ->composeFromKey('queue_failed_job')
                ->setTo($user)
                ->send();

            if ($user->email) {
                $sentEmails[strtolower($user->email)] = true;
            }
        }

        foreach ($settings->getFailedJobEmails() as $email) {
            $emailKey = strtolower($email);

            if (isset($sentEmails[$emailKey])) {
                continue;
            }

            Craft::$app->getMailer()
                ->composeFromKey('queue_failed_job', [
                    'user' => [
                        'email' => $email,
                        'friendlyName' => $email,
                    ],
                ])
                ->setTo($email)
                ->send();
        }
    }

    public function checkStalledQueue(): ?array
    {
        $settings = QueueMonitor::$plugin->getSettings();

        if (!$settings->getMonitorStalledQueue()) {
            return null;
        }

        $threshold = $settings->getStalledQueueThreshold();
        $thresholdTimestamp = time() - ($threshold * 60);
        $job = $this->_getStalledQueueJob($thresholdTimestamp);

        if (!$job) {
            return null;
        }

        $cache = Craft::$app->getCache();

        if ($cache->get(self::STALLED_QUEUE_ALERT_CACHE_KEY)) {
            return $job;
        }

        $mutex = Craft::$app->getMutex();

        if (!$mutex->acquire(self::STALLED_QUEUE_ALERT_MUTEX_KEY, 0)) {
            return $job;
        }

        try {
            // Recheck after serialization because another process may have sent the alert while this one was waiting.
            if ($cache->get(self::STALLED_QUEUE_ALERT_CACHE_KEY)) {
                return $job;
            }

            $this->_sendStalledQueueEmail($job);
            $this->_sendStalledQueueWebhook($job);

            $cache->set(self::STALLED_QUEUE_ALERT_CACHE_KEY, true, $settings->getStalledQueueCheckInterval() * 60);
        } finally {
            $mutex->release(self::STALLED_QUEUE_ALERT_MUTEX_KEY);
        }

        return $job;
    }


    // Private Methods
    // =========================================================================

    private function _getStalledQueueJob(int $thresholdTimestamp): ?array
    {
        $job = (new Query())
            ->from(Table::QUEUE)
            ->where(['dateReserved' => null])
            ->andWhere(['fail' => false])
            ->andWhere(['<=', new Expression('[[timePushed]] + [[delay]]'), $thresholdTimestamp])
            ->orderBy([
                'priority' => SORT_ASC,
                'id' => SORT_ASC,
            ])
            ->one();

        return $job ?: null;
    }

    private function _sendStalledQueueEmail(array $job): void
    {
        $settings = QueueMonitor::$plugin->getSettings();

        if (!$settings->getSendStalledQueueEmail()) {
            return;
        }

        $sentEmails = [];
        $messageParams = $this->_stalledQueueMessageParams($job);

        foreach ($settings->getStalledQueueUsers() as $user) {
            Craft::$app->getMailer()
                ->composeFromKey('queue_stalled', $messageParams)
                ->setTo($user)
                ->send();

            if ($user->email) {
                $sentEmails[strtolower($user->email)] = true;
            }
        }

        foreach ($settings->getStalledQueueEmails() as $email) {
            $emailKey = strtolower($email);

            if (isset($sentEmails[$emailKey])) {
                continue;
            }

            Craft::$app->getMailer()
                ->composeFromKey('queue_stalled', array_merge($messageParams, [
                    'user' => [
                        'email' => $email,
                        'friendlyName' => $email,
                    ],
                ]))
                ->setTo($email)
                ->send();
        }
    }

    private function _sendStalledQueueWebhook(array $job): void
    {
        $settings = QueueMonitor::$plugin->getSettings();
        $webhookUrl = $settings->getStalledQueueWebhook();

        if (!$webhookUrl) {
            return;
        }

        $age = $this->_stalledQueueJobAge($job);
        $description = $job['description'] ?: Craft::t('queue-monitor', 'Unknown queue job');
        $queueUrl = UrlHelper::cpUrl('utilities/queue-manager');
        $responseStatusCode = null;

        try {
            $client = Craft::createGuzzleClient([
                'handler' => HandlerStack::create(new CurlHandler()),
            ]);
            $connectTimeout = (float)$client->getConfig(RequestOptions::CONNECT_TIMEOUT);
            $timeout = (float)$client->getConfig(RequestOptions::TIMEOUT);
            $request = WebhookUrl::prepareRequest($webhookUrl, array_filter([
                RequestOptions::CURL => $client->getConfig(RequestOptions::CURL),
                RequestOptions::ON_STATS => $client->getConfig(RequestOptions::ON_STATS),
                RequestOptions::VERIFY => $client->getConfig(RequestOptions::VERIFY),
            ], fn(mixed $value): bool => $value !== null));

            // Keep stricter project-wide limits while ensuring this synchronous request is always bounded.
            $response = $client->post($request['url'], array_merge($request['options'], [
                RequestOptions::CONNECT_TIMEOUT => $connectTimeout > 0 ? min($connectTimeout, self::WEBHOOK_CONNECT_TIMEOUT) : self::WEBHOOK_CONNECT_TIMEOUT,
                RequestOptions::TIMEOUT => $timeout > 0 ? min($timeout, self::WEBHOOK_TIMEOUT) : self::WEBHOOK_TIMEOUT,
                RequestOptions::JSON => [
                    'text' => Craft::t('queue-monitor', 'Queue appears stalled on {siteName}. Oldest available job "{description}" has been waiting {age} minutes. Review it at {url}', [
                        'siteName' => Craft::$app->getSystemName(),
                        'description' => $description,
                        'age' => $age,
                        'url' => $queueUrl,
                    ]),
                ],
            ]));
            $responseStatusCode = $response->getStatusCode();

            if ($responseStatusCode < 200 || $responseStatusCode >= 300) {
                throw new RuntimeException('Webhook endpoint did not accept the notification.');
            }
        } catch (Throwable $e) {
            Craft::error($this->_stalledQueueWebhookErrorMessage($e, $responseStatusCode), __METHOD__);
        }
    }

    private function _stalledQueueWebhookErrorMessage(Throwable $error, ?int $responseStatusCode = null): string
    {
        $message = 'Unable to send stalled queue webhook notification.';

        if ($responseStatusCode === null && $error instanceof RequestException && $error->hasResponse()) {
            $responseStatusCode = $error->getResponse()->getStatusCode();
        }

        if ($responseStatusCode !== null) {
            $message .= ' HTTP status: ' . $responseStatusCode . '.';
        }

        return $message;
    }

    private function _stalledQueueMessageParams(array $job): array
    {
        return [
            'job' => $job,
            'jobDescription' => $job['description'] ?: Craft::t('queue-monitor', 'Unknown queue job'),
            'jobAge' => $this->_stalledQueueJobAge($job),
            'threshold' => QueueMonitor::$plugin->getSettings()->getStalledQueueThreshold(),
        ];
    }

    private function _stalledQueueJobAge(array $job): int
    {
        return (int)floor((time() - ((int)$job['timePushed'] + (int)$job['delay'])) / 60);
    }
}
