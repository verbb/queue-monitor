<?php
namespace verbb\queuemonitor\helpers;

use Craft;

use yii\base\InvalidArgumentException;

use CraftCms\UrlValidator\UrlValidationException;
use CraftCms\UrlValidator\UrlValidator;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;

class WebhookUrl
{
    // Constants
    // =========================================================================

    private const DISALLOWED_IP_RANGES = [
        ['192.0.2.0', 24],
        ['192.88.99.0', 24],
        ['198.51.100.0', 24],
        ['203.0.113.0', 24],
        ['224.0.0.0', 4],
        ['2001:2::', 48],
        ['2001:10::', 28],
        ['2001:20::', 28],
        ['2001:db8::', 32],
        ['3fff::', 20],
    ];


    // Properties
    // =========================================================================

    private static mixed $_resolver = null;


    // Public Methods
    // =========================================================================

    /** Build transport safeguards for a public HTTPS webhook URL. */
    public static function prepareRequest(string $url, array $baseOptions = []): array
    {
        try {
            $uri = new Uri($url);
        } catch (\InvalidArgumentException $e) {
            throw self::_invalidUrl($e);
        }

        $parts = parse_url($url);
        $host = self::_normalizeHost($uri->getHost());

        if (!is_array($parts) || $host === '' || strtolower($uri->getScheme()) !== 'https') {
            throw self::_invalidUrl();
        }

        if (array_key_exists('user', $parts) || array_key_exists('pass', $parts)) {
            throw self::_invalidUrl();
        }

        // Reject raw and ambiguous numeric addresses so DNS validation and pinning always share one canonical host.
        if (
            filter_var($host, FILTER_VALIDATE_IP)
            || preg_match('/^(?:0x[0-9a-f]+|[0-9]+)(?:\.(?:0x[0-9a-f]+|[0-9]+))*$/i', $host)
        ) {
            throw self::_invalidUrl();
        }

        $requestUrl = (string)$uri->withHost($host);
        $validator = new UrlValidator(self::$_resolver, ['allowedSchemes' => ['https']]);

        try {
            $addresses = $validator->validate($requestUrl);

            foreach ($addresses as $address) {
                if (!self::_isPublicAddress($validator, $address)) {
                    throw new UrlValidationException('Webhook URL resolves to a prohibited address.');
                }
            }
        } catch (UrlValidationException $e) {
            throw self::_invalidUrl($e);
        }

        $options = $baseOptions;
        $configuredOnStats = $options[RequestOptions::ON_STATS] ?? null;
        $approvedAddresses = array_filter(array_map(static fn(string $address) => @inet_pton($address), $addresses));

        $options[RequestOptions::ALLOW_REDIRECTS] = false;
        $options[RequestOptions::PROXY] = '';
        $options[RequestOptions::VERIFY] = is_string($options[RequestOptions::VERIFY] ?? null) ? $options[RequestOptions::VERIFY] : true;
        $options[RequestOptions::ON_STATS] = static function(TransferStats $stats) use ($approvedAddresses, $configuredOnStats): void {
            $primaryIp = $stats->getHandlerStat('primary_ip');
            $primaryAddress = is_string($primaryIp) ? @inet_pton(trim($primaryIp, '[]')) : false;

            if ($primaryAddress === false || !in_array($primaryAddress, $approvedAddresses, true)) {
                throw new InvalidArgumentException(Craft::t('queue-monitor', 'Webhook connection did not use the validated public address.'));
            }

            if (is_callable($configuredOnStats)) {
                $configuredOnStats($stats);
            }
        };

        $curlOptions = is_array($options[RequestOptions::CURL] ?? null) ? $options[RequestOptions::CURL] : [];

        foreach (['CURLOPT_ALTSVC', 'CURLOPT_ALTSVC_CTRL', 'CURLOPT_CONNECT_TO', 'CURLOPT_PORT', 'CURLOPT_PRE_PROXY', 'CURLOPT_PROXY', 'CURLOPT_UNIX_SOCKET_PATH', 'CURLOPT_URL'] as $optionName) {
            if (defined($optionName)) {
                unset($curlOptions[constant($optionName)]);
            }
        }

        $port = $uri->getPort() ?? 443;
        $curlAddresses = array_map(static fn(string $address): string => str_contains($address, ':') ? "[$address]" : $address, $addresses);

        if ((curl_version()['version_number'] ?? PHP_INT_MAX) < 0x073B00) {
            $curlAddresses = [reset($curlAddresses)];
        }

        $curlOptions[CURLOPT_FOLLOWLOCATION] = false;
        $curlOptions[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
        $curlOptions[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTPS;
        $curlOptions[CURLOPT_RESOLVE] = [sprintf('%s:%d:%s', $host, $port, implode(',', $curlAddresses))];
        $curlOptions[CURLOPT_SSL_VERIFYPEER] = true;
        $curlOptions[CURLOPT_SSL_VERIFYHOST] = 2;
        $options[RequestOptions::CURL] = $curlOptions;

        return [
            'url' => $requestUrl,
            'options' => $options,
        ];
    }

    /** Test seam for deterministic DNS resolution. */
    public static function setResolver(?callable $resolver): void
    {
        self::$_resolver = $resolver;
    }


    // Private Methods
    // =========================================================================

    private static function _invalidUrl(?\Throwable $previous = null): InvalidArgumentException
    {
        return new InvalidArgumentException(
            Craft::t('queue-monitor', 'Webhook URL must be a public HTTPS address.'),
            previous: $previous,
        );
    }

    private static function _isPublicAddress(UrlValidator $validator, string $address): bool
    {
        if (!$validator->validateIp($address)) {
            return false;
        }

        if (str_contains($address, ':') && !self::_ipInRange($address, '2000::', 3)) {
            return false;
        }

        foreach (self::DISALLOWED_IP_RANGES as [$subnet, $bits]) {
            if (self::_ipInRange($address, $subnet, $bits)) {
                return false;
            }
        }

        return true;
    }

    private static function _normalizeHost(string $host): string
    {
        return rtrim(strtolower(trim($host, '[]')), '.');
    }

    private static function _ipInRange(string $ip, string $subnet, int $bits): bool
    {
        $packedIp = @inet_pton($ip);
        $packedSubnet = @inet_pton($subnet);

        if ($packedIp === false || $packedSubnet === false || strlen($packedIp) !== strlen($packedSubnet)) {
            return false;
        }

        $bytes = intdiv($bits, 8);
        $remainingBits = $bits % 8;

        if ($bytes && substr($packedIp, 0, $bytes) !== substr($packedSubnet, 0, $bytes)) {
            return false;
        }

        if (!$remainingBits) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;

        return (ord($packedIp[$bytes]) & $mask) === (ord($packedSubnet[$bytes]) & $mask);
    }
}
