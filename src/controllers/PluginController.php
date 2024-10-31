<?php
namespace verbb\queuemonitor\controllers;

use verbb\queuemonitor\QueueMonitor;
use verbb\queuemonitor\models\Settings;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use yii\web\Response;

class PluginController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionSettings(): Response
    {
        /* @var Settings $settings */
        $settings = QueueMonitor::$plugin->getSettings();

        return $this->renderTemplate('queue-monitor/settings', [
            'settings' => $settings,
        ]);
    }
}
