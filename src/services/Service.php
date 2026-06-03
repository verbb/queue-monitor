<?php
namespace verbb\queuemonitor\services;

use verbb\queuemonitor\QueueMonitor;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\UrlHelper;

use Throwable;
use yii\db\Expression;
use yii\queue\ExecEvent;

class Service extends Component
{
    // Constants
    // =========================================================================

    private const STALLED_QUEUE_ALERT_CACHE_KEY = 'queue-monitor:stalled-queue-alert';


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

        $this->_sendStalledQueueEmail($job);
        $this->_sendStalledQueueWebhook($job);

        $cache->set(self::STALLED_QUEUE_ALERT_CACHE_KEY, true, $settings->getStalledQueueCheckInterval() * 60);

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

        try {
            Craft::createGuzzleClient()->post($webhookUrl, [
                'json' => [
                    'text' => Craft::t('queue-monitor', 'Queue appears stalled on {siteName}. Oldest available job "{description}" has been waiting {age} minutes. Review it at {url}', [
                        'siteName' => Craft::$app->getSystemName(),
                        'description' => $description,
                        'age' => $age,
                        'url' => $queueUrl,
                    ]),
                ],
            ]);
        } catch (Throwable $e) {
            Craft::error('Unable to send stalled queue webhook notification: ' . $e->getMessage(), __METHOD__);
        }
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
