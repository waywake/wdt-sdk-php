<?php

declare(strict_types=1);

namespace WayWake\WdtSdkPhp;

use InvalidArgumentException;

class WdtManager
{
    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * @var array<string, WdtErpClient>
     */
    private array $clients = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function connection(?string $name = null): WdtErpClient
    {
        $resolvedName = $name ?? $this->getDefaultConnection();

        if (! isset($this->clients[$resolvedName])) {
            $this->clients[$resolvedName] = $this->build($this->getConnectionConfig($resolvedName));
        }

        return $this->clients[$resolvedName];
    }

    /**
     * @param array<string, mixed> $connection
     */
    public function build(array $connection): WdtErpClient
    {
        return new WdtErpClient(
            (string) ($connection['url'] ?? 'http://wdt.wangdian.cn'),
            $this->requireString($connection, 'sid'),
            $this->requireString($connection, 'key'),
            $this->requireString($connection, 'secret'),
            (bool) ($connection['multi_tenant_mode'] ?? false),
        );
    }

    public function purge(?string $name = null): void
    {
        if ($name === null) {
            $this->clients = [];

            return;
        }

        unset($this->clients[$name]);
    }

    public function getDefaultConnection(): string
    {
        return (string) ($this->config['default'] ?? 'default');
    }

    public function __call(string $method, array $arguments): mixed
    {
        return $this->connection()->{$method}(...$arguments);
    }

    /**
     * @return array<string, mixed>
     */
    private function getConnectionConfig(string $name): array
    {
        $connections = $this->config['connections'] ?? [];

        if (! is_array($connections) || ! isset($connections[$name]) || ! is_array($connections[$name])) {
            throw new InvalidArgumentException(sprintf('未配置旺店通连接 [%s]', $name));
        }

        return $connections[$name];
    }

    /**
     * @param array<string, mixed> $connection
     */
    private function requireString(array $connection, string $key): string
    {
        $value = $connection[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new InvalidArgumentException(sprintf('旺店通连接缺少必填配置 [%s]', $key));
        }

        return $value;
    }
}
