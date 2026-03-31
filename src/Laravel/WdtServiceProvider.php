<?php

declare(strict_types=1);

namespace WayWake\WdtSdkPhp\Laravel;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use WayWake\WdtSdkPhp\WdtErpClient;
use WayWake\WdtSdkPhp\WdtManager;

class WdtServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/wdt-sdk.php', 'wdt-sdk');

        $this->app->singleton(WdtManager::class, function ($app): WdtManager {
            /** @var array<string, mixed> $config */
            $config = (array) $app['config']->get('wdt-sdk', []);

            return new WdtManager($config);
        });

        $this->app->singleton('wdt-sdk', fn ($app): WdtManager => $app->make(WdtManager::class));
        $this->app->bind(WdtErpClient::class, fn ($app): WdtErpClient => $app->make(WdtManager::class)->connection());
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/wdt-sdk.php' => config_path('wdt-sdk.php'),
        ], 'wdt-sdk-config');
    }

    public function provides(): array
    {
        return [
            'wdt-sdk',
            WdtManager::class,
            WdtErpClient::class,
        ];
    }
}
