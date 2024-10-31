<?php
namespace verbb\queuemonitor;

use verbb\queuemonitor\base\PluginTrait;
use verbb\queuemonitor\models\Settings;
use verbb\queuemonitor\variables\QueueMonitorVariable;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterEmailMessagesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\queue\Queue;
use craft\services\SystemMessages;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;

use yii\base\Event;
use yii\queue\ExecEvent;

class QueueMonitor extends Plugin
{
    // Properties
    // =========================================================================

    public bool $hasCpSettings = true;
    public string $schemaVersion = '1.0.0';


    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->_setPluginComponents();
        $this->_setLogging();
        $this->_registerVariables();
        $this->_registerCraftEventListeners();
        $this->_registerEmailMessages();

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerCpRoutes();
        }
    }

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('queue-monitor/settings'));
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================

    private function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['queue-monitor'] = 'queue-monitor/plugin/settings';
            $event->rules['queue-monitor/settings'] = 'queue-monitor/plugin/settings';
        });
    }

    private function _registerVariables(): void
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $event->sender->set('queueMonitor', QueueMonitorVariable::class);
        });
    }

    private function _registerCraftEventListeners(): void
    {
        $settings = $this->getSettings();

        // Change the default Queue service handling
        if ($settings->getRestartFailedJobs()) {
            Craft::$app->getQueue()->attempts = $settings->restartMaxTries;
            Craft::$app->getQueue()->ttr = $settings->restartInterval;
        }

        Event::on(Queue::class, Queue::EVENT_AFTER_ERROR, function(ExecEvent $event) {
            $this->getService()->sendFailedJobEmail($event);
        });
    }

    private function _registerEmailMessages(): void
    {
        Event::on(SystemMessages::class, SystemMessages::EVENT_REGISTER_MESSAGES, function(RegisterEmailMessagesEvent $event) {
            $event->messages[] = [
                'key' => 'queue_failed_job',
                'heading' => Craft::t('queue-monitor', 'queue_failed_job_heading'),
                'subject' => Craft::t('queue-monitor', 'queue_failed_job_subject'),
                'body' => Craft::t('queue-monitor', 'queue_failed_job_body'),
            ];
        });
    }
}
