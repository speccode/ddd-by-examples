<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain\Entities;

use DateTimeImmutable;
use Speccode\Availability\Domain\ValueObjects\PublishDate;
use Speccode\Availability\Domain\ValueObjects\Week;
use Speccode\Availability\Domain\ValueObjects\Weekday;
use Speccode\Kernel\Domain\ValueObjects\TimeRange;

final class OpeningHours
{
    private PublishDate $publishDate;
    private Week $week;

    public function __construct(PublishDate $publishDate, Week $week)
    {
        $this->publishDate = $publishDate;
        $this->week = $week;
    }

    public function publishDate(): PublishDate
    {
        return $this->publishDate;
    }

    public function change(Week $week)
    {
        $this->week = $week;
    }

    public function isTimeRangeAvailable(DateTimeImmutable $date, TimeRange $timeRange): bool
    {
        $weekdayName = strtolower($date->format('l'));

        return Weekday::$weekdayName($timeRange)->fitIn($this->week->$weekdayName());
    }
}
