<?php
namespace verbb\queuemonitor\variables;

use verbb\queuemonitor\QueueMonitor;

class QueueMonitorVariable
{
    // Public Methods
    // =========================================================================

    public function getPlugin(): QueueMonitor
    {
        return QueueMonitor::$plugin;
    }
    
}