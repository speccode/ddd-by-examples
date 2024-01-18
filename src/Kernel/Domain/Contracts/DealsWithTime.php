<?php

declare(strict_types=1);

namespace Speccode\Kernel\Domain\Contracts;

use Carbon\CarbonImmutable;
use DateTimeImmutable;

trait DealsWithTime
{
    public function isToday(DateTimeImmutable $date): bool
    {
        return $this->now()->format('Ymd') === $date->format('Ymd');
    }

    public function isDayInPast(DateTimeImmutable $date): bool
    {
        return (int) $this->now()->format('Ymd') > (int) $date->format('Ymd');
    }

    public function isInPast(DateTimeImmutable $date): bool
    {
        return $this->now() > $date;
    }

    public function now(): DateTimeImmutable
    {
        return CarbonImmutable::now();
    }
}
