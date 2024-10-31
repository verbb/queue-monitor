<?php

return [
  //
  // Email Messages
  //
  'queue_failed_job_heading' => 'When a queue job has failed:',
  'queue_failed_job_subject' => 'Queue Job failed on {{ siteName }}',
  'queue_failed_job_body' => "Hey {{ user.friendlyName }},\n\n" .
    "A queue job has failed on {{ siteName }}. Visit {{ cpUrl('utilities/queue-manager') }} to review.",
];