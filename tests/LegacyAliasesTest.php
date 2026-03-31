<?php

declare(strict_types=1);

namespace WayWake\WdtSdkPhp\Tests;

use PHPUnit\Framework\TestCase;
use WayWake\WdtSdkPhp\Pager as NamespacedPager;
use WayWake\WdtSdkPhp\WdtErpClient as NamespacedWdtErpClient;
use WayWake\WdtSdkPhp\WdtErpException as NamespacedWdtErpException;

final class LegacyAliasesTest extends TestCase
{
    public function testLegacyAliasesPointToNamespacedClasses(): void
    {
        self::assertTrue(class_exists('WdtErpClient'));
        self::assertTrue(class_exists('Pager'));
        self::assertTrue(class_exists('WdtErpException'));
        self::assertTrue(is_a(\WdtErpClient::class, NamespacedWdtErpClient::class, true));
        self::assertTrue(is_a(\Pager::class, NamespacedPager::class, true));
        self::assertTrue(is_a(\WdtErpException::class, NamespacedWdtErpException::class, true));
    }

    public function testLegacyAliasesCanInstantiateObjects(): void
    {
        $client = new \WdtErpClient('http://127.0.0.1:30000', 'sid', 'key', 'secret:salt');
        $pager = new \Pager(10, 1, true);

        self::assertInstanceOf(NamespacedWdtErpClient::class, $client);
        self::assertInstanceOf(NamespacedPager::class, $pager);
        self::assertSame(10, $pager->getPageSize());
        self::assertSame(1, $pager->getPageNo());
        self::assertTrue($pager->getCalcTotal());
    }
}
