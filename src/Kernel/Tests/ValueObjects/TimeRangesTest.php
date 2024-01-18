<?php

declare(strict_types=1);

namespace Speccode\Kernel\Tests\ValueObjects;

use PHPUnit\Framework\TestCase;
use Speccode\Kernel\Domain\ValueObjects\TimeRange;
use Speccode\Kernel\Domain\ValueObjects\TimeRanges;

class TimeRangesTest extends TestCase
{
    /** @test */
    public function combinesASetOfTimeRanges(): void
    {
        // given
        $timeRanges = TimeRanges::create([
            TimeRange::fromString('17:00-19:30'),
            TimeRange::fromString('09:00-11:00'),
            TimeRange::fromString('08:00-10:00'),
        ]);
        $expectedTimeRanges = [
            '17:00-19:30',
            '08:00-11:00',
        ];

        // when
        $combinedTimeRanges = $timeRanges->combine();

        // then
        $this->assertEquals($expectedTimeRanges, $combinedTimeRanges->toStringsArray());
    }

    /** @test */
    public function sortsTimeRanges(): void
    {
        // given
        $timeRanges = TimeRanges::create([
            TimeRange::fromString('17:00-19:30'),
            TimeRange::fromString('09:00-09:30'),
            TimeRange::fromString('08:00-10:00'),
        ]);
        $expectedTimeRanges = [
            '08:00-10:00',
            '09:00-09:30',
            '17:00-19:30',
        ];

        // when
        $sortedTimeRanges = $timeRanges->sorted();

        // then
        $this->assertEquals($expectedTimeRanges, $sortedTimeRanges->toStringsArray());
    }
}
