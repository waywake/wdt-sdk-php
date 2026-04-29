# AGENTS.md

供 AI 编码代理在本仓库工作时参考。

## 项目概览

本仓库是旺店通旗舰版 OpenAPI 的 Composer SDK。

- 包名：`waywake/wdt-sdk`
- 运行环境：PHP `^8.4`
- 主命名空间：`WayWake\WdtSdkPhp`
- 源码目录：`src/`
- 测试目录：`tests/`
- Laravel 集成：`src/Laravel/`，包含自动发现的服务提供者和 Facade

## 常用命令

请在仓库根目录运行命令。

```bash
composer install
composer test
vendor/bin/phpunit
```

`composer test` 是标准测试命令，对应执行 `phpunit`。

## 代码风格

- PHP 源码和测试文件使用 `declare(strict_types=1);`。
- 遵循 PSR-4 自动加载：
  - `WayWake\WdtSdkPhp\` 映射到 `src/`
  - `WayWake\WdtSdkPhp\Tests\` 映射到 `tests/`
- 优先使用类型化属性、类型化参数和类型化返回值。
- 公共 API 变更要保守；这是会被外部项目消费的 SDK 包。
- 除非变更明确需要，否则保留现有中文异常信息。
- 不要把框架依赖引入核心 SDK；Laravel 专属逻辑应放在 `src/Laravel/`。

## 架构说明

- `WdtErpClient` 是核心 OpenAPI 客户端，负责构建签名请求、发送 POST 请求、解析响应，并在接口返回正数状态码时抛出 `WdtErpException`。
- `Pager` 表示分页请求参数。
- `WdtManager` 根据配置构建并缓存具名客户端。
- `src/Laravel/WdtServiceProvider.php` 注册包配置、容器绑定和 Laravel 集成。
- `config/wdt-sdk.php` 是可发布的 Laravel 配置文件。

修改请求签名、URL 规范化、JSON 编解码或异常行为时，请在 `tests/WdtErpClientTest.php` 添加或更新聚焦测试。

修改 Laravel 绑定、Facade 行为、配置发布或具名连接时，请在 `tests/Laravel/WdtServiceProviderTest.php` 添加或更新聚焦测试。

## 测试建议

修改 PHP 行为后，结束前运行：

```bash
composer test
```

窄范围变更也可以先运行指定 PHPUnit 文件：

```bash
vendor/bin/phpunit tests/WdtErpClientTest.php
vendor/bin/phpunit tests/Laravel/WdtServiceProviderTest.php
```

如果依赖缺失，请先运行 `composer install`。

## 仓库规范

- 不要编辑 `vendor/`。
- 不要提交 PHPUnit 生成的缓存文件。
- 谨慎修改 `composer.lock`：只有依赖变更确实有意为之时才更新它。
- 工作区可能已有用户改动，不要回滚无关修改。
- 保持变更小而聚焦，只处理当前请求涉及的行为。
- 提交代码时，commit message 必须遵循 Conventional Commits，例如 `docs: update project documentation`、`fix: handle expired token`、`feat: add user group support`。

## 文档

修改安装步骤、公开用法示例、Laravel 配置或兼容性说明时，请同步更新 `README.md`。
