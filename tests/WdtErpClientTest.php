<?php

declare(strict_types=1);

namespace WayWake\WdtSdkPhp\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WayWake\WdtSdkPhp\Pager;
use WayWake\WdtSdkPhp\WdtErpClient;
use WayWake\WdtSdkPhp\WdtErpException;

final class WdtErpClientTest extends TestCase
{
    public function testBuildRequestNormalizesUrlAndEncodesPagerArguments(): void
    {
        $client = new WdtErpClient('http://127.0.0.1:30000/', 'sid', 'key', 'secret:salt', true);
        $pager = new Pager(50, 2, true);

        [$body, $url] = $client->buildRequest('goods.Goods.queryWithSpec', $pager, [
            ['start_time' => '2020-01-01 00:00:00', 'hide_deleted' => 1],
        ]);

        self::assertJsonStringEqualsJsonString(
            '[{"start_time":"2020-01-01 00:00:00","hide_deleted":1}]',
            $body,
        );
        self::assertStringStartsWith('http://127.0.0.1:30000/openapi?', $url);
        self::assertStringContainsString('method=goods.Goods.queryWithSpec', $url);
        self::assertStringContainsString('page_size=50', $url);
        self::assertStringContainsString('page_no=2', $url);
        self::assertStringContainsString('calc_total=1', $url);
        self::assertStringContainsString('sid=sid', $url);
        self::assertStringContainsString('key=key', $url);
        self::assertStringContainsString('salt=salt', $url);
        self::assertMatchesRegularExpression('/(?:^|&)sign=[a-f0-9]{32}(?:&|$)/', $url);
    }

    public function testThreeArgumentConstructorUsesDefaultOpenApiUrl(): void
    {
        $client = new WdtErpClient('sid', 'key', 'secret:salt');

        [, $url] = $client->buildRequest('demo.method', null, []);

        self::assertStringStartsWith('http://wdt.wangdian.cn/openapi?', $url);
    }

    public function testConstructorRejectsMalformedSecret(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('appsecret 必须为 "secret:salt" 格式');

        new WdtErpClient('http://127.0.0.1:30000', 'sid', 'key', 'invalid-secret');
    }

    public function testCallReturnsDataPayload(): void
    {
        $client = new FakeWdtErpClient('http://127.0.0.1:30000', 'sid', 'key', 'secret:salt');
        $client->response = '{"status":0,"data":{"order_no":"SO001"}}';

        $result = $client->call('trade.push', ['order_no' => 'SO001']);

        self::assertSame('trade.push', $client->lastBuiltMethod);
        self::assertNull($client->lastBuiltPager);
        self::assertSame([['order_no' => 'SO001']], $client->lastBuiltArgs);
        self::assertIsObject($result);
        self::assertSame('SO001', $result->order_no);
    }

    public function testPageCallReturnsFullResponseWhenCalcTotalEnabled(): void
    {
        $client = new FakeWdtErpClient('http://127.0.0.1:30000', 'sid', 'key', 'secret:salt');
        $client->response = '{"status":0,"data":[{"spec_no":"SKU001"}],"total_count":1}';
        $pager = new Pager(1, 0, true);

        $result = $client->pageCall('stock.query', $pager, ['spec_no' => 'SKU001']);

        self::assertSame('stock.query', $client->lastBuiltMethod);
        self::assertSame($pager, $client->lastBuiltPager);
        self::assertSame([['spec_no' => 'SKU001']], $client->lastBuiltArgs);
        self::assertIsObject($result);
        self::assertSame(1, $result->total_count);
        self::assertSame('SKU001', $result->data[0]->spec_no);
    }

    public function testPageCallReturnsDataOnlyWhenCalcTotalDisabled(): void
    {
        $client = new FakeWdtErpClient('http://127.0.0.1:30000', 'sid', 'key', 'secret:salt');
        $client->response = '{"status":0,"data":[{"spec_no":"SKU001"}],"total_count":1}';
        $pager = new Pager(1, 0, false);

        $result = $client->pageCall('stock.query', $pager, ['spec_no' => 'SKU001']);

        self::assertIsArray($result);
        self::assertSame('SKU001', $result[0]->spec_no);
    }

    public function testCallExWrapsMethodNameIntoScriptExtensionRequest(): void
    {
        $client = new FakeWdtErpClient('http://127.0.0.1:30000', 'sid', 'key', 'secret:salt');
        $client->response = '{"status":0,"data":{"success":true}}';

        $result = $client->callEx('custom.script', ['foo' => 'bar']);

        self::assertSame('system.ScriptExtension.call', $client->lastBuiltMethod);
        self::assertSame(['custom.script', ['foo' => 'bar']], $client->lastBuiltArgs);
        self::assertTrue($result->success);
    }

    public function testThrowsDomainExceptionForPositiveStatus(): void
    {
        $client = new FakeWdtErpClient('http://127.0.0.1:30000', 'sid', 'key', 'secret:salt');
        $client->response = '{"status":7,"message":"boom"}';

        $this->expectException(WdtErpException::class);
        $this->expectExceptionCode(7);
        $this->expectExceptionMessage('boom');

        $client->call('trade.push', ['order_no' => 'SO001']);
    }

    public function testThrowsRuntimeExceptionForNonJsonResponse(): void
    {
        $client = new FakeWdtErpClient('http://127.0.0.1:30000', 'sid', 'key', 'secret:salt');
        $client->response = '<html>fatal</html>';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('旺店通接口返回了非 JSON 响应');

        $client->call('trade.push', ['order_no' => 'SO001']);
    }
}

final class FakeWdtErpClient extends WdtErpClient
{
    public string $response = '{"status":0,"data":null}';
    public ?string $lastBuiltMethod = null;
    public ?Pager $lastBuiltPager = null;
    public array $lastBuiltArgs = [];

    public function buildRequest(string $method, ?Pager $pager, array $args): array
    {
        $this->lastBuiltMethod = $method;
        $this->lastBuiltPager = $pager;
        $this->lastBuiltArgs = $args;

        return parent::buildRequest($method, $pager, $args);
    }

    public function sendRequest(string $body, string $serviceUrl): string
    {
        return $this->response;
    }
}
