<?php

namespace Speccode\Kernel\Tests\Traits;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeInterface;

trait TestsTime
{
    public function setTestNow(DateTimeInterface $dateTime): void
    {
        $dateTime = new CarbonImmutable($dateTime);
        Carbon::setTestNow($dateTime);
        CarbonImmutable::setTestNow($dateTime);
    }

    public function resetTestNow(): void
    {
        Carbon::setTestNow(false);
        CarbonImmutable::setTestNow(false);
    }
}
