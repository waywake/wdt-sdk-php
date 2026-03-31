<?php

declare(strict_types=1);

namespace WayWake\WdtSdkPhp;

class Pager
{
    public function __construct(
        private int $pageSize,
        private int $pageNo = 0,
        private bool $calcTotal = false,
    ) {
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getPageNo(): int
    {
        return $this->pageNo;
    }

    public function getCalcTotal(): bool
    {
        return $this->calcTotal;
    }
}
