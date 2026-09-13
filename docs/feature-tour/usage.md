# Usage

## Failed Job Email
Queue Monitor can send out an email as soon as a queue job has failed. This can be helpful to alert site admins of potential issues with queue jobs.

Nominate a User Group in Queue Monitor's settings, with each user in that group receiving an email notification when a queue job fails. You can also add individual email addresses for recipients who do not need to be Craft users. The content of this email is customisable via the Craft **System Messages** utility.

## Auto-Restart Failed Jobs
You can also easily manage auto-restarting of failed queue jobs. By default, only one attempt is made for a queue job, and if it fails, no more attempts are made. Queue Monitor allows you to manage how many attempts a failed job should retry, along with setting the duration in seconds between retries.

## Choose a Retry Policy

A retry is useful when a job failed because a service was temporarily unavailable. It will not repair an invalid template or missing credentials. For example, a notification group can alert the site's maintainers while a short retry policy gives an interrupted connection time to recover.

Set the recipient group or email addresses and the retry settings in the control panel. When a job fails, compare its error and attempt count in Craft's queue with the received notification. Correct a persistent cause before retrying again, then confirm that the job completes. For jobs that send data to an external service, also check the receiving account: completion in Craft and the expected remote result are both part of the check.
