<?php

use verbb\queuemonitor\services\Service;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

$vendorPath = getenv('VERBB_QUEUE_MONITOR_TEST_VENDOR') ?: dirname(__DIR__, 2) . '/vendor';

if (!is_file($vendorPath . '/autoload.php')) {
    throw new RuntimeException('Set VERBB_QUEUE_MONITOR_TEST_VENDOR to a Craft 5 vendor directory.');
}

require $vendorPath . '/autoload.php';
require $vendorPath . '/yiisoft/yii2/Yii.php';
require $vendorPath . '/craftcms/cms/src/Craft.php';
require dirname(__DIR__, 2) . '/src/services/Service.php';

function webhookLogFixtureAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$service = new Service();
$formatter = new ReflectionMethod($service, '_stalledQueueWebhookErrorMessage');
$request = new Request('POST', 'https://hooks.example.test/services/path-secret?token=query-secret');
$response = new Response(403, [], 'response-body-secret');
$requestError = RequestException::create($request, $response);
$requestMessage = $formatter->invoke($service, $requestError);

webhookLogFixtureAssert(
    $requestMessage === 'Unable to send stalled queue webhook notification. HTTP status: 403.',
    'HTTP failures must retain only the numeric response status.',
);

foreach (['path-secret', 'query-secret', 'response-body-secret', $requestError->getMessage()] as $secret) {
    webhookLogFixtureAssert(!str_contains($requestMessage, $secret), 'HTTP failure logs must not contain request or response secrets.');
}

$connectError = new ConnectException('Connection failed for query-secret', $request);
$connectMessage = $formatter->invoke($service, $connectError);

webhookLogFixtureAssert(
    $connectMessage === 'Unable to send stalled queue webhook notification.',
    'Transport failures must use a stable generic message.',
);
webhookLogFixtureAssert(!str_contains($connectMessage, 'query-secret'), 'Transport failure logs must not contain exception details.');

$explicitStatusMessage = $formatter->invoke(
    $service,
    new RuntimeException('Webhook endpoint did not accept the notification.'),
    429,
);

webhookLogFixtureAssert(
    $explicitStatusMessage === 'Unable to send stalled queue webhook notification. HTTP status: 429.',
    'Explicitly handled non-success responses must retain their numeric status.',
);

echo "Queue Monitor webhook log security fixture passed.\n";
