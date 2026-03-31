<?php

declare(strict_types=1);

namespace WayWake\WdtSdkPhp\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WayWake\WdtSdkPhp\Pager;
use WayWake\WdtSdkPhp\WdtErpClient;
use WayWake\WdtSdkPhp\WdtManager;

final class WdtManagerTest extends TestCase
{
    public function testReturnsCachedDefaultConnection(): void
    {
        $manager = new WdtManager([
            'default' => 'default',
            'connections' => [
                'default' => [
                    'url' => 'http://127.0.0.1:30000',
                    'sid' => 'sid',
                    'key' => 'key',
                    'secret' => 'secret:salt',
                    'multi_tenant_mode' => true,
                ],
            ],
        ]);

        $first = $manager->connection();
        $second = $manager->connection();

        self::assertInstanceOf(WdtErpClient::class, $first);
        self::assertSame($first, $second);
    }

    public function testCanResolveNamedConnectionAndPurgeIt(): void
    {
        $manager = new WdtManager([
            'default' => 'primary',
            'connections' => [
                'primary' => [
                    'url' => 'http://127.0.0.1:30000',
                    'sid' => 'sid-primary',
                    'key' => 'key-primary',
                    'secret' => 'secret:salt',
                ],
                'secondary' => [
                    'url' => 'http://127.0.0.2:30000',
                    'sid' => 'sid-secondary',
                    'key' => 'key-secondary',
                    'secret' => 'secret:salt2',
                ],
            ],
        ]);

        $first = $manager->connection('secondary');
        $manager->purge('secondary');
        $second = $manager->connection('secondary');

        self::assertNotSame($first, $second);
    }

    public function testMagicCallDelegatesToDefaultConnection(): void
    {
        $manager = new WdtManager([
            'default' => 'default',
            'connections' => [
                'default' => [
                    'url' => 'http://127.0.0.1:30000',
                    'sid' => 'sid',
                    'key' => 'key',
                    'secret' => 'secret:salt',
                ],
            ],
        ]);

        [$body, $url] = $manager->buildRequest('demo.method', new Pager(10, 1, true), [['foo' => 'bar']]);

        self::assertJsonStringEqualsJsonString('[{"foo":"bar"}]', $body);
        self::assertStringContainsString('method=demo.method', $url);
        self::assertStringContainsString('page_size=10', $url);
    }

    public function testThrowsForMissingConnection(): void
    {
        $manager = new WdtManager([
            'default' => 'missing',
            'connections' => [],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('未配置旺店通连接 [missing]');

        $manager->connection();
    }

    public function testThrowsForMissingRequiredConnectionValue(): void
    {
        $manager = new WdtManager([
            'default' => 'default',
            'connections' => [
                'default' => [
                    'url' => 'http://127.0.0.1:30000',
                    'sid' => '',
                    'key' => 'key',
                    'secret' => 'secret:salt',
                ],
            ],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('旺店通连接缺少必填配置 [sid]');

        $manager->connection();
    }
}
