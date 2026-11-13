# Console Commands

Queue Monitor includes console commands you can run from your Craft project’s directory.

## Stalled Queue Monitoring

::: reference
### `queue-monitor/stalled/check`

Checks whether the queue has stalled and sends the stalled queue alert when it has. The queue counts as stalled when a job has been waiting longer than [`stalledQueueThreshold`](../get-started/configuration.md#stalledqueuethreshold) minutes without a worker picking it up. The alert goes to the user group, email addresses and webhook in your settings, and another isn’t sent until [`stalledQueueCheckInterval`](../get-started/configuration.md#stalledqueuecheckinterval) minutes have passed, however often the command runs. It does nothing unless [`monitorStalledQueue`](../get-started/configuration.md#monitorstalledqueue) is on.

The check runs outside the queue, so it still works when the queue worker has stopped. Run it from cron, such as every five minutes.

```shell
php craft queue-monitor/stalled/check
```

```bash
*/5 * * * * /path/to/project/craft queue-monitor/stalled/check
```
:::
