# 旺店通旗舰版 PHP Composer SDK

这份仓库已整理为可发布的 Composer 包，兼容 PHP 8.4+。

## 安装

发布到私有 Composer 仓库或 Packagist 后：

```bash
composer require waywake/wdt-sdk-php
```

如果当前只是本地仓库，也可以在业务项目里先用 `path` 或 `vcs` 仓库方式接入。

## Composer 用法

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use WayWake\WdtSdkPhp\Pager;
use WayWake\WdtSdkPhp\WdtErpClient;

$client = new WdtErpClient(
    'http://127.0.0.1:30000',
    'wdterp30',
    'your-app-key',
    'your-secret:your-salt',
    true,
);

$pager = new Pager(50, 0, true);
$result = $client->pageCall('goods.Goods.queryWithSpec', $pager, [
    'start_time' => '2020-01-01 00:00:00',
    'end_time' => '2020-01-20 00:00:00',
]);
```

## Laravel 12

Laravel 12 安装后会通过 Composer 自动发现服务提供者和 Facade。

发布配置：

```bash
php artisan vendor:publish --tag=wdt-sdk-config
```

配置文件示例：

```php
return [
    'default' => env('WDT_CONNECTION', 'default'),
    'connections' => [
        'default' => [
            'url' => env('WDT_URL', 'http://wdt.wangdian.cn'),
            'sid' => env('WDT_SID', ''),
            'key' => env('WDT_KEY', ''),
            'secret' => env('WDT_SECRET', ''),
            'multi_tenant_mode' => (bool) env('WDT_MULTI_TENANT_MODE', false),
        ],
    ],
];
```

容器和 Facade 用法：

```php
use WayWake\WdtSdkPhp\WdtErpClient;
use WayWake\WdtSdkPhp\Laravel\Facades\Wdt;

$client = app(WdtErpClient::class);
$result = $client->call('trade.Query', ['shop_no' => 'SHOP001']);

$sameResult = Wdt::call('trade.Query', ['shop_no' => 'SHOP001']);
$backupClient = Wdt::connection('backup');
```

## 兼容旧接入

Composer 自动加载里同时提供了旧类名别名：

- `WdtErpClient`
- `Pager`
- `WdtErpException`

这意味着旧代码在改成 `require vendor/autoload.php` 后，通常不需要立刻改类名。

如果你仍然是手工下载源码方式，也可以继续：

```php
require_once __DIR__ . '/wdtsdk.php';
```

## 这次整理包含的改动

- 增加 `composer.json` 和 PSR-4 自动加载
- 将核心类移入 `src/` 命名空间 `WayWake\\WdtSdkPhp`
- 保留根目录 `wdtsdk.php` 兼容入口
- 修正请求头拼接和非 JSON 响应处理
- 明确约束 PHP 版本为 `^8.4`

## 运行测试

```bash
composer install
composer test
```
