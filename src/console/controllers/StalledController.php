<?php
namespace verbb\queuemonitor\console\controllers;

use verbb\queuemonitor\QueueMonitor;

use craft\console\Controller;

use yii\console\ExitCode;

class StalledController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionCheck(): int
    {
        $job = QueueMonitor::$plugin->getService()->checkStalledQueue();

        if ($job) {
            $description = $job['description'] ?: 'Unknown queue job';

            $this->stdout("Stalled queue job detected: {$description}\n");

            return ExitCode::OK;
        }

        $this->stdout("No stalled queue job detected.\n");

        return ExitCode::OK;
    }
}
