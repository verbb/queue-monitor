<?php

return [
  //
  // Email Messages
  //
  'queue_failed_job_heading' => 'When a queue job has failed:',
  'queue_failed_job_subject' => 'Queue Job failed on {{ siteName }}',
  'queue_failed_job_body' => "Hey {{ user.friendlyName }},\n\n" .
    "A queue job has failed on {{ siteName }}. Visit {{ cpUrl('utilities/queue-manager') }} to review.",
  'queue_stalled_heading' => 'When the queue appears stalled:',
  'queue_stalled_subject' => 'Queue appears stalled on {{ siteName }}',
  'queue_stalled_body' => "Hey {{ user.friendlyName }},\n\n" .
    "The queue appears stalled on {{ siteName }}. The oldest available job, \"{{ jobDescription }}\", has been waiting {{ jobAge }} minutes, which is longer than the configured {{ threshold }} minute threshold.\n\n" .
    "Visit {{ cpUrl('utilities/queue-manager') }} to review.",


  'Auto-Restart Failed Jobs' => 'Auto-Restart Failed Jobs',
  'Email Address' => 'Email Address',
  'Enter any additional email addresses to receive failed queue job notifications. These recipients do not need to be Craft users.' => 'Enter any additional email addresses to receive failed queue job notifications. These recipients do not need to be Craft users.',
  'Enter any additional email addresses to receive stalled queue notifications. These recipients do not need to be Craft users.' => 'Enter any additional email addresses to receive stalled queue notifications. These recipients do not need to be Craft users.',
  'Enter a webhook URL to send stalled queue notifications.' => 'Enter a webhook URL to send stalled queue notifications.',
  'General Settings' => 'General Settings',
  'Maximum Retries' => 'Maximum Retries',
  'Monitor Stalled Queue' => 'Monitor Stalled Queue',
  'None' => 'None',
  'Notification Email Addresses' => 'Notification Email Addresses',
  'Notification User Group' => 'Notification User Group',
  'Queue Monitor' => 'Queue Monitor',
  'Queue appears stalled on {siteName}. Oldest available job "{description}" has been waiting {age} minutes. Review it at {url}' => 'Queue appears stalled on {siteName}. Oldest available job "{description}" has been waiting {age} minutes. Review it at {url}',
  'Retry Interval' => 'Retry Interval',
  'Select the user group to receive failed queue job notifications. Each user in this group will receive an emails.' => 'Select the user group to receive failed queue job notifications. Each user in this group will receive an emails.',
  'Select the user group to receive stalled queue notifications. Each user in this group will receive an email.' => 'Select the user group to receive stalled queue notifications. Each user in this group will receive an email.',
  'Send Failed Queue Email' => 'Send Failed Queue Email',
  'Send Stalled Queue Email' => 'Send Stalled Queue Email',
  'Settings' => 'Settings',
  'Stalled Queue Check Interval' => 'Stalled Queue Check Interval',
  'Stalled Queue Threshold' => 'Stalled Queue Threshold',
  'The minimum number of minutes between stalled queue notifications.' => 'The minimum number of minutes between stalled queue notifications.',
  'The number of minutes a queue job can wait before the queue is considered stalled.' => 'The number of minutes a queue job can wait before the queue is considered stalled.',
  'The number of retries a failed job should retry until.' => 'The number of retries a failed job should retry until.',
  'The number seconds between retries.' => 'The number seconds between retries.',
  'Unknown queue job' => 'Unknown queue job',
  'Whether to automatically restart a failed queue job.' => 'Whether to automatically restart a failed queue job.',
  'Whether to monitor the queue for jobs that are waiting but not being processed.' => 'Whether to monitor the queue for jobs that are waiting but not being processed.',
  'Whether to send an email to notify users when a queue job has failed.' => 'Whether to send an email to notify users when a queue job has failed.',
  'Whether to send an email when the queue appears stalled.' => 'Whether to send an email when the queue appears stalled.',
  'Webhook URL' => 'Webhook URL',
];