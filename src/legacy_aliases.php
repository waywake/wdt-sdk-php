<?php

declare(strict_types=1);

use WayWake\WdtSdkPhp\Pager as NamespacedPager;
use WayWake\WdtSdkPhp\WdtErpClient as NamespacedWdtErpClient;
use WayWake\WdtSdkPhp\WdtErpException as NamespacedWdtErpException;

if (! class_exists('WdtErpException', false)) {
    class_alias(NamespacedWdtErpException::class, 'WdtErpException');
}

if (! class_exists('Pager', false)) {
    class_alias(NamespacedPager::class, 'Pager');
}

if (! class_exists('WdtErpClient', false)) {
    class_alias(NamespacedWdtErpClient::class, 'WdtErpClient');
}
