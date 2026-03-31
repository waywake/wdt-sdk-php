<?php

declare(strict_types=1);

namespace WayWake\WdtSdkPhp\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

class Wdt extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'wdt-sdk';
    }
}
