<?php

namespace Speccode\Availability\Tests\Domain\ValueObjects;

use InvalidArgumentException;
use Speccode\Availability\Domain\ValueObjects\Weekday;
use Speccode\Kernel\Domain\ValueObjects\DateTimeRange;
use Speccode\Kernel\Domain\ValueObjects\TimeRange;
use PHPUnit\Framework\TestCase;

class WeekdayTest extends TestCase
{
    /** @test */
    public function WeekdaySuccessfullyInstantiatedFromString()
    {
        $this->assertInstanceOf(
            Weekday::class,
            Weekday::fromString('monday', '7:30-15:30')
        );
    }

    /** @test */
    public function weekdaySuccessfullyInstantiatedFromWeekdayNameMethod()
    {
        $this->assertInstanceOf(
            Weekday::class,
            Weekday::monday('8:00-16:00')
        );

        $this->assertInstanceOf(
            Weekday::class,
            Weekday::friday('8:00-16:00')
        );

        $this->assertInstanceOf(
            Weekday::class,
            Weekday::friday(TimeRange::fromString('8:00-16:00'))
        );
    }

    /** @test */
    public function weekdaySuccessfullyInstantiatedFromDateTimeRange(): void
    {
        $this->assertInstanceOf(
            Weekday::class,
            Weekday::fromDateTimeRange(
                DateTimeRange::fromString('2021-10-19 10:00-12:00')
            )
        );
    }

    /** @test */
    public function throwExceptionWhenCreatedFromStringWithInvalidName()
    {
        $this->expectException(InvalidArgumentException::class);

        Weekday::fromString('foobar', '8:00-16:00');
    }

    /** @test */
    public function throwExceptionWhenCreatedFromWeekdayNameMethodWithInvalidName()
    {
        $this->expectException(InvalidArgumentException::class);

        Weekday::foobar('8:00-16:00');
    }

    /** @test */
    public function checkIfOneInstanceOfWeekdayWithTimeRangeCanFitIntoOther()
    {
        $weekday = Weekday::monday('8:00-16:00');
        $beginningOfDay = Weekday::monday('8:00-10:00');
        $endOfDay = Weekday::monday('15:00-16:00');
        $wholeDay = Weekday::monday('8:00-16:00');
        $notInRange = Weekday::monday('16:00-20:00');
        $startsBefore = Weekday::monday('6:00-12:00');
        $endsAfter = Weekday::monday('14:00-18:00');

        $notInRange->fitIn($weekday);

        $this->assertTrue($beginningOfDay->fitIn($weekday));
        $this->assertTrue($endOfDay->fitIn($weekday));
        $this->assertTrue($wholeDay->fitIn($weekday));
        $this->assertFalse($notInRange->fitIn($weekday));
        $this->assertFalse($startsBefore->fitIn($weekday));
        $this->assertFalse($endsAfter->fitIn($weekday));
    }

    /** @test */
    public function throwExceptionWhenTryingToCheckIfWeekdayFitInOtherWeekday()
    {
        $this->expectException(InvalidArgumentException::class);

        $monday = Weekday::monday('8:00-16:00');
        $tuesday = Weekday::tuesday('10:00-12:00');

        $tuesday->fitIn($monday);
    }

    /**
     * @test
     * @dataProvider provideWeekdaysToCompare
     */
    public function equalsWhenComparedToOtherWeekdayWithSameValues(Weekday $weekday, Weekday $otherWeekday, bool $expected): void
    {
        $this->assertSame($expected, $weekday->equals($otherWeekday));
    }

    public static function provideWeekdaysToCompare(): array
    {
        return [
            'same weekday and time range' => [Weekday::monday('08:00-16:00'), Weekday::monday('08:00-16:00'), true],
            'same weekday, different time range' => [
                Weekday::monday('08:00-16:00'),
                Weekday::monday('10:00-16:00'),
                false,
            ],
            'different weekday, same time range' => [
                Weekday::monday('08:00-16:00'),
                Weekday::tuesday('08:00-16:00'),
                false,
            ],
            'same weekday, other time range is null' => [Weekday::monday('08:00-16:00'), Weekday::monday(), false],
            'same weekday, first time range is null' => [Weekday::monday(), Weekday::monday('08:00-16:00'), false],
            'same weekday, both time ranges are null' => [Weekday::monday(), Weekday::monday(), true],
            'different weekday, both time ranges are null' => [Weekday::monday(), Weekday::tuesday(), false],
        ];
    }
}
