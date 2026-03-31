<?php

declare(strict_types=1);

namespace WayWake\WdtSdkPhp\Tests\Laravel;

use Orchestra\Testbench\TestCase;
use WayWake\WdtSdkPhp\Laravel\Facades\Wdt;
use WayWake\WdtSdkPhp\Laravel\WdtServiceProvider;
use WayWake\WdtSdkPhp\WdtErpClient;
use WayWake\WdtSdkPhp\WdtManager;

final class WdtServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [WdtServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('wdt-sdk.default', 'primary');
        $app['config']->set('wdt-sdk.connections.primary', [
            'url' => 'http://127.0.0.1:30000',
            'sid' => 'sid-primary',
            'key' => 'key-primary',
            'secret' => 'secret:salt',
            'multi_tenant_mode' => true,
        ]);
        $app['config']->set('wdt-sdk.connections.backup', [
            'url' => 'http://127.0.0.2:30000',
            'sid' => 'sid-backup',
            'key' => 'key-backup',
            'secret' => 'secret:salt2',
            'multi_tenant_mode' => false,
        ]);
    }

    public function testContainerResolvesManagerAndDefaultClient(): void
    {
        $manager = $this->app->make(WdtManager::class);
        $client = $this->app->make(WdtErpClient::class);
        $aliasedManager = $this->app->make('wdt-sdk');

        self::assertInstanceOf(WdtManager::class, $manager);
        self::assertSame($manager, $aliasedManager);
        self::assertInstanceOf(WdtErpClient::class, $client);

        [, $url] = $client->buildRequest('demo.method', null, []);
        self::assertStringStartsWith('http://127.0.0.1:30000/openapi?', $url);
    }

    public function testFacadeCanResolveNamedConnectionAndProxyCalls(): void
    {
        $backupClient = Wdt::connection('backup');
        [$body, $url] = Wdt::buildRequest('demo.method', null, [['foo' => 'bar']]);

        self::assertInstanceOf(WdtErpClient::class, $backupClient);
        self::assertJsonStringEqualsJsonString('[{"foo":"bar"}]', $body);
        self::assertStringStartsWith('http://127.0.0.1:30000/openapi?', $url);

        [, $backupUrl] = $backupClient->buildRequest('demo.method', null, []);
        self::assertStringStartsWith('http://127.0.0.2:30000/openapi?', $backupUrl);
    }

    public function testProviderPublishesConfigFile(): void
    {
        $paths = WdtServiceProvider::pathsToPublish(WdtServiceProvider::class, 'wdt-sdk-config');

        self::assertContains($this->app->configPath('wdt-sdk.php'), array_values($paths));
    }
}
