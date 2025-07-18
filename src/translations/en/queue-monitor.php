<?php

return [
  //
  // Email Messages
  //
  'queue_failed_job_heading' => 'When a queue job has failed:',
  'queue_failed_job_subject' => 'Queue Job failed on {{ siteName }}',
  'queue_failed_job_body' => "Hey {{ user.friendlyName }},\n\n" .
    "A queue job has failed on {{ siteName }}. Visit {{ cpUrl('utilities/queue-manager') }} to review.",


  'Auto-Restart Failed Jobs' => 'Auto-Restart Failed Jobs',
  'General Settings' => 'General Settings',
  'Maximum Retries' => 'Maximum Retries',
  'None' => 'None',
  'Notification User Group' => 'Notification User Group',
  'Queue Monitor' => 'Queue Monitor',
  'Retry Interval' => 'Retry Interval',
  'Select the user group to receive failed queue job notifications. Each user in this group will receive an emails.' => 'Select the user group to receive failed queue job notifications. Each user in this group will receive an emails.',
  'Send Failed Queue Email' => 'Send Failed Queue Email',
  'Settings' => 'Settings',
  'The number of retries a failed job should retry until.' => 'The number of retries a failed job should retry until.',
  'The number seconds between retries.' => 'The number seconds between retries.',
  'Whether to automatically restart a failed queue job.' => 'Whether to automatically restart a failed queue job.',
  'Whether to send an email to notify users when a queue job has failed.' => 'Whether to send an email to notify users when a queue job has failed.',
];