<?php

declare(strict_types=1);

namespace WayWake\WdtSdkPhp;

use InvalidArgumentException;
use JsonException;
use RuntimeException;
use stdClass;

class WdtErpClient
{
    private const DEFAULT_URL = 'http://wdt.wangdian.cn/openapi';
    private const SDK_VERSION = 'php-composer-1.0';
    private const WDT_TIMESTAMP_OFFSET = 1325347200;

    private string $url = self::DEFAULT_URL;
    private string $sid = '';
    private string $key = '';
    private string $secret = '';
    private string $salt = '';
    private string $version = '1.0';
    private bool $multiTenantMode = false;

    public function __construct(mixed ...$arguments)
    {
        $count = count($arguments);

        if ($count === 3) {
            [$sid, $key, $secret] = $arguments;
            $this->initialize(self::DEFAULT_URL, (string) $sid, (string) $key, (string) $secret);

            return;
        }

        if ($count === 4 || $count === 5) {
            [$url, $sid, $key, $secret] = $arguments;
            $multiTenantMode = (bool) ($arguments[4] ?? false);

            $this->initialize((string) $url, (string) $sid, (string) $key, (string) $secret, $multiTenantMode);

            return;
        }

        throw new InvalidArgumentException('参数不合法');
    }

    public function buildRequest(string $method, ?Pager $pager, array $args): array
    {
        $request = [
            'sid' => $this->sid,
            'key' => $this->key,
            'salt' => $this->salt,
            'method' => $method,
            'timestamp' => time() - self::WDT_TIMESTAMP_OFFSET,
            'v' => $this->version,
        ];

        if ($pager !== null) {
            $request['page_size'] = $pager->getPageSize();
            $request['page_no'] = $pager->getPageNo();
            $request['calc_total'] = $pager->getCalcTotal() ? 1 : 0;
        }

        $body = $this->encodeBody($args);
        $request['body'] = $body;

        $this->makeSign($request);

        unset($request['body']);

        return [$body, $this->url . '?' . http_build_query($request)];
    }

    public function sendRequest(string $body, string $serviceUrl): string
    {
        $headers = [
            'Content-Type: application/json',
            'X-Version-SDK: ' . self::SDK_VERSION,
        ];

        if ($this->multiTenantMode) {
            $headers[] = 'Connection: close';
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers) . "\r\n",
                'content' => $body,
                'ignore_errors' => true,
                'timeout' => 30,
            ],
        ]);

        $response = @file_get_contents($serviceUrl, false, $context);

        if ($response === false) {
            $message = error_get_last()['message'] ?? '请求旺店通接口失败';
            throw new RuntimeException($message);
        }

        return $response;
    }

    public function isJson(string $string): bool
    {
        if ($string === '') {
            return false;
        }

        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }

    public function call(string $method, mixed ...$args): mixed
    {
        $json = $this->execute($method, null, $args);

        return $json->data ?? null;
    }

    public function pageCall(string $method, Pager $pager, mixed ...$args): mixed
    {
        $json = $this->execute($method, $pager, $args);

        if (! $pager->getCalcTotal()) {
            return $json->data ?? null;
        }

        return $json;
    }

    public function callEx(string $method, mixed ...$args): mixed
    {
        $json = $this->execute('system.ScriptExtension.call', null, [$method, ...$args]);

        return $json->data ?? null;
    }

    private function initialize(
        string $url,
        string $sid,
        string $key,
        string $secret,
        bool $multiTenantMode = false,
    ): void {
        $this->url = $this->normalizeServiceUrl($url);
        $this->sid = $sid;
        $this->key = $key;
        [$this->secret, $this->salt] = $this->parseSecret($secret);
        $this->multiTenantMode = $multiTenantMode;
    }

    private function normalizeServiceUrl(string $url): string
    {
        $trimmedUrl = rtrim($url, '/');

        if (str_ends_with($trimmedUrl, '/openapi')) {
            return $trimmedUrl;
        }

        return $trimmedUrl . '/openapi';
    }

    private function parseSecret(string $secret): array
    {
        $parts = explode(':', $secret, 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new InvalidArgumentException('appsecret 必须为 "secret:salt" 格式');
        }

        return $parts;
    }

    private function makeSign(array &$request): void
    {
        ksort($request);

        $segments = [$this->secret];

        foreach ($request as $key => $value) {
            if ($key === 'sign') {
                continue;
            }

            $segments[] = (string) $key;
            $segments[] = (string) $value;
        }

        $segments[] = $this->secret;
        $request['sign'] = md5(implode('', $segments));
    }

    private function execute(string $method, ?Pager $pager, array $args): stdClass
    {
        [$body, $serviceUrl] = $this->buildRequest($method, $pager, $args);
        $response = $this->sendRequest($body, $serviceUrl);
        $json = $this->decodeResponse($response);

        if (isset($json->status) && (int) $json->status > 0) {
            throw new WdtErpException((string) ($json->message ?? '旺店通接口返回错误'), (int) $json->status);
        }

        return $json;
    }

    private function encodeBody(array $args): string
    {
        try {
            return json_encode($args, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('请求参数无法编码为 JSON: ' . $exception->getMessage(), 0, $exception);
        }
    }

    private function decodeResponse(string $response): stdClass
    {
        try {
            $decoded = json_decode($response, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('旺店通接口返回了非 JSON 响应: ' . $response, 0, $exception);
        }

        if (! $decoded instanceof stdClass) {
            throw new RuntimeException('旺店通接口返回了非对象 JSON 响应');
        }

        return $decoded;
    }
}
