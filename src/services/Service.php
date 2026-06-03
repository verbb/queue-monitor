<?php
namespace verbb\queuemonitor\services;

use verbb\queuemonitor\QueueMonitor;

use Craft;
use craft\base\Component;

use yii\queue\ExecEvent;

class Service extends Component
{
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
}
