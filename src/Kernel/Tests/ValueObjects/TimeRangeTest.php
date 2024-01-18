<?php

namespace Speccode\Kernel\Tests\ValueObjects;

use InvalidArgumentException;
use Speccode\Kernel\Domain\ValueObjects\Time;
use Speccode\Kernel\Domain\ValueObjects\TimeRange;
use PHPUnit\Framework\TestCase;

class TimeRangeTest extends TestCase
{
    /** @test */
    public function timeRangeSuccessfullyInstantiatedFromTimeStrings()
    {
        $this->assertInstanceOf(
            TimeRange::class,
            TimeRange::fromTimeStrings('8:00', '16:00'),
        );
    }

    /** @test */
    public function timeRangeSuccessfullyInstantiatedFromString()
    {
        $this->assertInstanceOf(
            TimeRange::class,
            TimeRange::fromString('8:00-16:00'),
        );
    }

    /** @test */
    public function timeRangeSuccessfullyInstantiatedFromTimeObjects()
    {
        $this->assertInstanceOf(
            TimeRange::class,
            TimeRange::fromTime(
                Time::fromString('8:00'),
                Time::fromString('12:00'),
            ),
        );
    }

    /** @test */
    public function toStringReturnsProperFormat()
    {
        $timeRange = TimeRange::fromTimeStrings('8:00', '16:00');

        $this->assertSame('08:00-16:00', $timeRange->toString());
    }

    /** @test */
    public function throwExceptionWhenEndTimeIsEarlierThanStartTime()
    {
        $this->expectException(InvalidArgumentException::class);

        TimeRange::fromTimeStrings('16:00', '10:00');
    }

    /** @test */
    public function throwExceptionWhenStartTimeAndEndTimeIsSame()
    {
        $this->expectException(InvalidArgumentException::class);

        TimeRange::fromTimeStrings('10:00', '10:00');
    }

    /** @test */
    public function lengthReturnsTimeDifferenceBetweenStartAndEnd()
    {
        $timeRange = TimeRange::fromString('10:00-16:00');

        $this->assertSame('06:00', $timeRange->length()->toString());
    }

    /** @test */
    public function hasTimeProperlyChecksGivenTime()
    {
        $timeRange = TimeRange::fromString('10:00-16:00');
        $timeAtBeginning = Time::fromString('10:00');
        $timeAtEnd = Time::fromString('16:00');
        $timeInMiddle = Time::fromString('13:00');
        $timeNotInTimeRange = Time::fromString('20:00');

        $this->assertTrue($timeRange->hasTime($timeAtBeginning));
        $this->assertTrue($timeRange->hasTime($timeAtEnd));
        $this->assertTrue($timeRange->hasTime($timeInMiddle));
        $this->assertFalse($timeRange->hasTime($timeNotInTimeRange));
    }

    /**
     * @test
     * @dataProvider provideTimeRangesForComparison
     */
    public function equalsCorrectlyComparesGivenTimeRanges(string $timeRange, string $otherTimeRange, bool $expected): void
    {
        $timeRange = TimeRange::fromString($timeRange);
        $otherTimeRange = TimeRange::fromString($otherTimeRange);

        $this->assertSame($expected, $timeRange->equals($otherTimeRange));
    }

    public static function provideTimeRangesForComparison(): array
    {
        return [
            ['10:00-12:00', '10:00-12:00', true],
            ['05:01-12:12', '05:01-12:12', true],
            ['10:00-12:00', '10:00-11:59', false],
            ['10:00-12:00', '14:00-16:00', false],
        ];
    }

    /**
     * @test
     * @dataProvider provideTimeRangesForOverlappingComparison
     */
    public function overlapsComparesTwoTimeRanges(string $timeRange, string $otherTimeRange, bool $expectedResult): void
    {
        $timeRange = TimeRange::fromString($timeRange);
        $otherTimeRange = TimeRange::fromString($otherTimeRange);

        $this->assertEquals($expectedResult, $timeRange->overlaps($otherTimeRange));
    }

    public static function provideTimeRangesForOverlappingComparison(): array
    {
        return [
            ['10:00-12:00', '12:00-17:00', true],
            ['05:01-12:12', '03:01-05:01', true],
            ['04:01-12:12', '03:01-05:01', true],
            ['04:01-12:12', '05:01-09:01', true],
            ['05:01-09:01', '04:01-12:12', true],
            ['12:00-13:00', '10:00-11:59', false],
            ['10:00-12:00', '14:00-16:00', false],
        ];
    }

    /**
     * @test
     * @dataProvider provideTimeRangesForCombination
     */
    public function combinesTwoOverlappedTimeRanges(string $timeRange, string $otherTimeRange, string $expectedResult): void
    {
        $timeRange = TimeRange::fromString($timeRange);
        $otherTimeRange = TimeRange::fromString($otherTimeRange);
        $expectedTimeRange = TimeRange::fromString($expectedResult);

        $combinedTimeRange = $timeRange->combine($otherTimeRange);

        $this->assertTrue($expectedTimeRange->equals($combinedTimeRange));
    }

    public static function provideTimeRangesForCombination(): array
    {
        return [
            ['10:00-12:00', '12:00-17:00', '10:00-17:00'],
            ['05:01-12:12', '03:01-05:01', '03:01-12:12'],
            ['04:01-12:12', '03:01-05:01', '03:01-12:12'],
        ];
    }

    /** @test */
    public function combinesThrowsExceptionIfTimeRangesDoNotOverlap(): void
    {
        $timeRange = TimeRange::fromString('08:00-12:00');
        $anotherTimeRange = TimeRange::fromString('02:00-04:00');

        $this->expectException(InvalidArgumentException::class);

        $timeRange->combine($anotherTimeRange);
    }
}
