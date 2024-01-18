<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain\Specifications;

use Speccode\Kernel\Domain\ValueObjects\TimeRange;

final class MinimumBookingTime
{
    public const MINUTES = 30;

    public static function isSatisfiedBy(TimeRange $timeRange): bool
    {
        return $timeRange->length()->asMinutes() >= self::MINUTES;
    }
}
