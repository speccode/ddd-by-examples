<?php

declare(strict_types=1);

namespace Speccode\Availability\Tests\Domain\ValueObjects;

use DateTimeImmutable;
use DomainException;
use Speccode\Availability\Domain\ValueObjects\Week;
use Speccode\Availability\Domain\ValueObjects\Weekday;
use Speccode\Kernel\Domain\ValueObjects\TimeRange;
use PHPUnit\Framework\TestCase;

class WeekTest extends TestCase
{
    private const OPENING_HOURS = '07:30-15:30';
    private const OTHER_OPENING_HOURS = '08:00-16:00';

    private function weekArray(): array
    {
        return [
            Weekday::monday(TimeRange::fromString(self::OPENING_HOURS)),
            Weekday::tuesday(TimeRange::fromString(self::OPENING_HOURS)),
            Weekday::wednesday(TimeRange::fromString(self::OPENING_HOURS)),
            Weekday::thursday(TimeRange::fromString(self::OPENING_HOURS)),
            Weekday::friday(TimeRange::fromString(self::OPENING_HOURS)),
            Weekday::saturday(TimeRange::fromString(self::OPENING_HOURS)),
            Weekday::sunday(),
        ];
    }

    private function weekStringArray(): array
    {
        return [
            'monday' => self::OPENING_HOURS,
            'tuesday' => self::OPENING_HOURS,
            'wednesday' => self::OTHER_OPENING_HOURS,
            'thursday' => self::OTHER_OPENING_HOURS,
            'friday' => self::OTHER_OPENING_HOURS,
            'saturday' => self::OTHER_OPENING_HOURS,
            'sunday' => '',
        ];
    }

    /** @test */
    public function weekSuccessfullyInstantiatedEmpty()
    {
        $this->assertInstanceOf(
            Week::class,
            Week::createEmpty()
        );
    }

    /** @test */
    public function weekSuccessfullyInstantiatedFromArray()
    {
        $this->assertInstanceOf(
            Week::class,
            Week::fromArray($this->weekArray())
        );
    }

    /** @test */
    public function weekSuccessfullyInstantiatedFromStringsArray()
    {
        $this->assertInstanceOf(
            Week::class,
            Week::fromStringsArray($this->weekStringArray())
        );
    }

    /** @test */
    public function weekSuccessfullyInstantiatedWhenSomeDaysOmitted()
    {
        $this->assertInstanceOf(
            Week::class,
            Week::fromArray([
                Weekday::monday(TimeRange::fromString(self::OTHER_OPENING_HOURS)),
            ])
        );
    }

    /** @test */
    public function omittingDaysWhenInstantiateWeekMakeThoseDaysEmpty()
    {
        $week = Week::fromArray([
            Weekday::monday(TimeRange::fromString(self::OTHER_OPENING_HOURS)),
        ]);

        $this->assertTrue($week->saturday()->isEmpty());
    }

    /** @test */
    public function throwExceptionWhenTryingToAddWeekdayToWeekCollection()
    {
        $this->expectException(DomainException::class);

        $week = Week::createEmpty();
        $week->add(Weekday::monday());
    }

    /** @test */
    public function weekdayCallReturnsWeekdayObject()
    {
        $week = Week::fromArray($this->weekArray());

        $this->assertSame('monday', $week->monday()->toString());
        $this->assertInstanceOf(Weekday::class, $week->friday());
    }

    /** @test */
    public function weekIsTraversable()
    {
        $week = Week::fromArray($this->weekArray());

        $this->assertIsIterable($week);
    }

    /** @test */
    public function weekHaveSevenUniqueDays()
    {
        $week = Week::createEmpty();

        $this->assertSame(7, $week->count());
    }

    /** @test */
    public function createdWeekContainsGivenTimeRanges()
    {
        $week = Week::fromArray($this->weekArray());

        $this->assertInstanceOf(Weekday::class, $week->monday());
        $this->assertNotNull($week->monday()->timeRange());
        $this->assertSame(self::OPENING_HOURS, $week->monday()->timeRange()->toString());
    }

    /** @test */
    public function replacingWeekdayAffectsObject()
    {
        $week = Week::fromArray($this->weekArray());

        $week = $week->replace(Weekday::monday('10:00-12:00'));

        $this->assertSame('10:00-12:00', $week->monday()->timeRange()->toString());
    }

    /** @test */
    public function replacingWeekdayReturnDifferentWeekObject()
    {
        $week = Week::fromArray($this->weekArray());

        $newWeek = $week->replace(Weekday::monday('10:00-12:00'));

        $this->assertNotSame($week, $newWeek);
    }

    /**
     * @test
     * @dataProvider provideWeeksForWeekendChecks
     */
    public function hasWeekendsAvailableIsTrueWhenOneOfTheWeekendDaysHasAnyTimeAvailable(
        ?string $saturday,
        ?string $sunday,
        bool    $expected
    ): void {
        //given
        $weekdays = [
            Weekday::saturday($saturday),
            Weekday::sunday($sunday),
        ];

        //when
        $week = Week::fromArray($weekdays);

        //then
        $this->assertSame($expected, $week->hasWeekendAvailable());
    }

    public static function provideWeeksForWeekendChecks(): array
    {
        return [
            'saturday available' => [self::OPENING_HOURS, null, true],
            'sunday available' => [null, self::OPENING_HOURS, true],
            'saturday and sunday available' => [self::OPENING_HOURS, self::OPENING_HOURS, true],
            'no weekend days available' => [null, null, false],
        ];
    }

    /** @test */
    public function getsWeekdayForGivenDate(): void
    {
        //given
        $week = Week::create($this->weekArray());

        //when
        $weekday = $week->getWeekdayForDate(new DateTimeImmutable('2021-11-01'));

        //then
        $this->assertSame('monday', $weekday->toString());
    }
}
