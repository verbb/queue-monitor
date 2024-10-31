# Usage

## Failed Job Email
Queue Monitor can send out an email as soon as a queue job has failed. This can be helpful to alert site admins of potential issues with queue jobs.

Nominate a User Group in Queue Monitor's settings, with each user in that group receiving an email notification when a queue job fails. The content of this email is customisable via the Craft **System Messages** utility.

## Auto-Restart Failed Jobs
You can also easily manage auto-restarting of failed queue jobs. By default, only one attempt is made for a queue job, and if it fails, no more attempts are made. Queue Monitor allows you to manage how many attempts a failed job should retry, along with setting the duration in seconds between retries.