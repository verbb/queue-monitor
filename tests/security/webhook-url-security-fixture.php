<?php

use verbb\queuemonitor\helpers\WebhookUrl;

use yii\base\InvalidArgumentException;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;

$vendorPath = getenv('VERBB_QUEUE_MONITOR_TEST_VENDOR') ?: dirname(__DIR__, 2) . '/vendor';

if (!is_file($vendorPath . '/autoload.php')) {
    throw new RuntimeException('Set VERBB_QUEUE_MONITOR_TEST_VENDOR to a Craft 5 vendor directory.');
}

require $vendorPath . '/autoload.php';
require $vendorPath . '/yiisoft/yii2/Yii.php';
require $vendorPath . '/craftcms/cms/src/Craft.php';
require dirname(__DIR__, 2) . '/src/helpers/WebhookUrl.php';

function webhookUrlFixtureAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function webhookUrlFixtureRejects(string $url): bool
{
    try {
        WebhookUrl::prepareRequest($url);
    } catch (InvalidArgumentException) {
        return true;
    }

    return false;
}

function webhookUrlFixtureStats(string $url, ?string $primaryIp): TransferStats
{
    return new TransferStats(
        new Request('POST', $url),
        null,
        0,
        null,
        $primaryIp === null ? [] : ['primary_ip' => $primaryIp],
    );
}

$answers = [
    'public.example.test' => ['93.184.216.34', '2606:4700:4700::1111'],
    'other-public.example.test' => ['1.1.1.1'],
    'mixed.example.test' => ['93.184.216.34', '10.0.0.10'],
    'private.example.test' => ['10.0.0.10'],
    'metadata.example.test' => ['169.254.169.254'],
    'documentation-v4.example.test' => ['192.0.2.10'],
    'relay.example.test' => ['192.88.99.10'],
    'translation.example.test' => ['64:ff9b:1::10'],
    'benchmark.example.test' => ['2001:2::10'],
    'documentation.example.test' => ['2001:db8::10'],
    'top-level-reserved.example.test' => ['4000::10'],
    'multicast.example.test' => ['224.0.0.10'],
    'unresolved.example.test' => [],
    'xn--bcher-kva.example.test' => ['93.184.216.34'],
];

WebhookUrl::setResolver(fn(string $host): array => $answers[rtrim(strtolower($host), '.')] ?? []);

try {
    $configuredOnStatsCalled = false;
    $configuredCurlOptions = [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_PROXY => 'http://proxy.example.test:8080',
        CURLOPT_TIMEOUT => 2,
    ];

    foreach (['CURLOPT_ALTSVC', 'CURLOPT_ALTSVC_CTRL'] as $optionName) {
        if (defined($optionName)) {
            $configuredCurlOptions[constant($optionName)] = $optionName === 'CURLOPT_ALTSVC' ? '/tmp/queue-monitor-altsvc' : 1;
        }
    }

    $request = WebhookUrl::prepareRequest('https://PUBLIC.EXAMPLE.TEST./hook?token=secret', [
        RequestOptions::CURL => $configuredCurlOptions,
        RequestOptions::ON_STATS => function() use (&$configuredOnStatsCalled): void {
            $configuredOnStatsCalled = true;
        },
        RequestOptions::PROXY => 'http://proxy.example.test:8080',
        RequestOptions::VERIFY => false,
    ]);
    $options = $request['options'];

    webhookUrlFixtureAssert($request['url'] === 'https://public.example.test/hook?token=secret', 'Public webhook hosts must be canonicalized without changing their path or query.');
    webhookUrlFixtureAssert($options[RequestOptions::ALLOW_REDIRECTS] === false, 'Webhook redirects must be disabled.');
    webhookUrlFixtureAssert($options[RequestOptions::PROXY] === '', 'Webhook requests must bypass proxies that can resolve a different origin.');
    webhookUrlFixtureAssert($options[RequestOptions::VERIFY] === true, 'Webhook TLS verification must remain enabled.');
    webhookUrlFixtureAssert($options[RequestOptions::CURL][CURLOPT_FOLLOWLOCATION] === false, 'Raw cURL redirects must be disabled.');
    webhookUrlFixtureAssert(!isset($options[RequestOptions::CURL][CURLOPT_PROXY]), 'Raw cURL proxies must be removed.');

    foreach (['CURLOPT_ALTSVC', 'CURLOPT_ALTSVC_CTRL'] as $optionName) {
        webhookUrlFixtureAssert(!defined($optionName) || !isset($options[RequestOptions::CURL][constant($optionName)]), 'Raw cURL Alt-Svc routing must be removed.');
    }

    webhookUrlFixtureAssert($options[RequestOptions::CURL][CURLOPT_TIMEOUT] === 2, 'Unrelated stricter cURL timeout settings must be preserved.');
    webhookUrlFixtureAssert(str_starts_with($options[RequestOptions::CURL][CURLOPT_RESOLVE][0], 'public.example.test:443:'), 'The canonical webhook host must be pinned to its validated addresses.');
    webhookUrlFixtureAssert(str_contains($options[RequestOptions::CURL][CURLOPT_RESOLVE][0], '[2606:4700:4700::1111]'), 'Pinned IPv6 addresses must use cURL bracket notation.');

    $options[RequestOptions::ON_STATS](webhookUrlFixtureStats($request['url'], '93.184.216.34'));
    $options[RequestOptions::ON_STATS](webhookUrlFixtureStats($request['url'], '2606:4700:4700::1111'));
    webhookUrlFixtureAssert($configuredOnStatsCalled, 'Configured transfer-stat callbacks must be composed after peer verification.');

    foreach (['1.1.1.1', '10.0.0.10', null] as $primaryIp) {
        $rejected = false;

        try {
            $options[RequestOptions::ON_STATS](webhookUrlFixtureStats($request['url'], $primaryIp));
        } catch (InvalidArgumentException) {
            $rejected = true;
        }

        webhookUrlFixtureAssert($rejected, 'The connected peer must exactly match a validated DNS answer.');
    }

    webhookUrlFixtureAssert(WebhookUrl::prepareRequest('https://xn--bcher-kva.example.test/hook')['url'] === 'https://xn--bcher-kva.example.test/hook', 'Punycode webhook domains must remain supported.');
    $customPortRequest = WebhookUrl::prepareRequest('https://public.example.test:8443/hook', [RequestOptions::VERIFY => '/tmp/custom-ca.pem']);
    webhookUrlFixtureAssert(str_starts_with($customPortRequest['options'][RequestOptions::CURL][CURLOPT_RESOLVE][0], 'public.example.test:8443:'), 'Public HTTPS webhook services on custom ports must remain supported.');
    webhookUrlFixtureAssert($customPortRequest['options'][RequestOptions::VERIFY] === '/tmp/custom-ca.pem', 'Configured CA bundles must remain supported.');

    foreach ([
        'http://public.example.test/hook',
        'https://user@public.example.test/hook',
        'https://@public.example.test/hook',
        'https:///hook',
        'https://127.0.0.1/hook',
        'https://2130706433/hook',
        'https://0177.0.0.1/hook',
        'https://unresolved.example.test/hook',
        'https://private.example.test/hook',
        'https://mixed.example.test/hook',
        'https://metadata.example.test/hook',
        'https://documentation-v4.example.test/hook',
        'https://relay.example.test/hook',
        'https://translation.example.test/hook',
        'https://benchmark.example.test/hook',
        'https://documentation.example.test/hook',
        'https://top-level-reserved.example.test/hook',
        'https://multicast.example.test/hook',
        'https://bücher.example.test/hook',
    ] as $url) {
        webhookUrlFixtureAssert(webhookUrlFixtureRejects($url), "$url must be rejected.");
    }

    echo "Queue Monitor webhook URL security fixture passed.\n";
} finally {
    WebhookUrl::setResolver(null);
}
