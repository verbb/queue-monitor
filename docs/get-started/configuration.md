# Configuration

You can customise Queue Monitor’s settings using a PHP configuration file. This is optional: each setting has a default, so you only need to include the values you want to change.

To override a setting, create `queue-monitor.php` in your Craft project’s `/config` directory and return an array of setting names and values. For example, the following will set the maximum retry count to five:

```php
<?php

return [
    'restartMaxTries' => 5,
];
```

All other settings keep their defaults. Add any further settings you want to change to the same array. The options below explain the available settings and their defaults.

## Configuration Options

::: reference
### `restartFailedJobs`

**Type:** `bool` · **Default:** `false`

Whether to automatically restart a failed queue job.
:::

::: reference
### `restartMaxTries`

**Type:** `int` · **Default:** `3`

The number of retries a failed job should retry until.
:::

::: reference
### `restartInterval`

**Type:** `int` · **Default:** `10`

The number seconds between retries.
:::

::: reference
### `sendFailedJobEmail`

**Type:** `bool` · **Default:** `false`

Whether to send an email to notify users when a queue job has failed.
:::

::: reference
### `failedJobUserGroup`

**Type:** `string|null` · **Default:** `null`

The user group (UID) to receive failed queue job notifications. Each user in this group will receive an emails.
:::

::: reference
### `failedJobEmails`

**Type:** `array` · **Default:** `[]`

Additional email addresses to receive failed queue job notifications. These recipients do not need to be Craft users.
:::

::: reference
### `monitorStalledQueue`

**Type:** `bool` · **Default:** `false`

Whether to monitor the queue for jobs that are waiting but not being processed.
:::

::: reference
### `stalledQueueThreshold`

**Type:** `int` · **Default:** `30`

The number of minutes a queue job can wait before the queue is considered stalled.
:::

::: reference
### `stalledQueueCheckInterval`

**Type:** `int` · **Default:** `30`

The minimum number of minutes between stalled queue notifications.
:::

::: reference
### `sendStalledQueueEmail`

**Type:** `bool` · **Default:** `false`

Whether to send an email when the queue appears stalled.
:::

::: reference
### `stalledQueueUserGroup`

**Type:** `string|null` · **Default:** `null`

The user group (UID) to receive stalled queue notifications. Each user in this group will receive an email.
:::

::: reference
### `stalledQueueEmails`

**Type:** `array` · **Default:** `[]`

Additional email addresses to receive stalled queue notifications. These recipients do not need to be Craft users.
:::

::: reference
### `stalledQueueWebhook`

**Type:** `string|null` · **Default:** `null`

A webhook URL to send stalled queue notifications.
:::


## Stalled Queue Monitoring
Stalled queue monitoring should be run from cron so it can detect when the queue worker itself has stopped. For example, run this command every five minutes:

```bash
*/5 * * * * /path/to/project/craft queue-monitor/stalled/check
```

## Control Panel
You can also manage configuration settings through the Control Panel by visiting Settings → Queue Monitor.
