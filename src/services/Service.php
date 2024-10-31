<?php
namespace verbb\queuemonitor\services;

use verbb\queuemonitor\QueueMonitor;
use verbb\queuemonitor\models\Settings;

use Craft;
use craft\base\Component;
use craft\helpers\Db;
use craft\helpers\UrlHelper;

use yii\queue\ExecEvent;

class Service extends Component
{
    // Public Methods
    // =========================================================================

    public function sendFailedJobEmail(ExecEvent $event): void
    {
        $settings = QueueMonitor::$plugin->getSettings();

        // Only send on first attempt, else we just get hassled
        if ((int)$event->attempt === 1) {
            if ($settings->getSendFailedJobEmail() && $users = $settings->getFailedJobUsers()) {
                foreach ($users as $user) {
                    Craft::$app->getMailer()
                        ->composeFromKey('queue_failed_job')
                        ->setTo($user)
                        ->send();
                }
            }
        }
    }
}
