# Configuration
Create a `queue-monitor.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

The below shows the defaults already used by Queue Monitor, so you don't need to add these options unless you want to modify the values.

```php
<?php

return [
    '*' => [
        'restartFailedJobs' => false,
        'restartMaxTries' => 3,
        'restartInterval' => 10,
        'sendFailedJobEmail' => false,
        'failedJobUserGroup' => null,
        'failedJobEmails' => [],
        'monitorStalledQueue' => false,
        'stalledQueueThreshold' => 30,
        'stalledQueueCheckInterval' => 30,
        'sendStalledQueueEmail' => false,
        'stalledQueueUserGroup' => null,
        'stalledQueueEmails' => [],
        'stalledQueueWebhook' => null,
    ]
];
```

## Configuration options
- `restartFailedJobs` - Whether to automatically restart a failed queue job.
- `restartMaxTries` - The number of retries a failed job should retry until.
- `restartInterval` - The number seconds between retries.
- `sendFailedJobEmail` - Whether to send an email to notify users when a queue job has failed.
- `failedJobUserGroup` - The user group (UID) to receive failed queue job notifications. Each user in this group will receive an emails.
- `failedJobEmails` - Additional email addresses to receive failed queue job notifications. These recipients do not need to be Craft users.
- `monitorStalledQueue` - Whether to monitor the queue for jobs that are waiting but not being processed.
- `stalledQueueThreshold` - The number of minutes a queue job can wait before the queue is considered stalled.
- `stalledQueueCheckInterval` - The minimum number of minutes between stalled queue notifications.
- `sendStalledQueueEmail` - Whether to send an email when the queue appears stalled.
- `stalledQueueUserGroup` - The user group (UID) to receive stalled queue notifications. Each user in this group will receive an email.
- `stalledQueueEmails` - Additional email addresses to receive stalled queue notifications. These recipients do not need to be Craft users.
- `stalledQueueWebhook` - A webhook URL to send stalled queue notifications.

## Stalled Queue Monitoring
Stalled queue monitoring should be run from cron so it can detect when the queue worker itself has stopped. For example, run this command every five minutes:

```bash
*/5 * * * * /path/to/project/craft queue-monitor/stalled/check
```

## Control Panel
You can also manage configuration settings through the Control Panel by visiting Settings → Queue Monitor.
