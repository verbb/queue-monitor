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
    ]
];
```

## Configuration options
- `restartFailedJobs` - Whether to automatically restart a failed queue job.
- `restartMaxTries` - The number of retries a failed job should retry until.
- `restartInterval` - The number seconds between retries.
- `sendFailedJobEmail` - Whether to send an email to notify users when a queue job has failed.
- `failedJobUserGroup` - The user group (UID) to receive failed queue job notifications. Each user in this group will receive an emails.

## Control Panel
You can also manage configuration settings through the Control Panel by visiting Settings → Queue Monitor.
