<?php

declare(strict_types=1);

namespace Speccode\Availability\Tests\Domain\Collections;

use DateTimeImmutable;
use Speccode\Availability\Domain\Collections\WeeklyAvailability;
use Speccode\Availability\Domain\Entities\OpeningHours;
use Speccode\Availability\Domain\ValueObjects\PublishDate;
use Speccode\Availability\Domain\ValueObjects\Week;
use Speccode\Kernel\Domain\ValueObjects\TimeRange;
use PHPUnit\Framework\TestCase;

class WeeklyAvailabilityTest extends TestCase
{
    private function availabilityFactory(string $publishDate, string $timeRange): OpeningHours
    {
        $week = Week::fromStringsArray([
            'monday' => $timeRange,
            'tuesday' => $timeRange,
            'wednesday' => $timeRange,
            'thursday' => $timeRange,
            'friday' => $timeRange,
        ]);

        return new OpeningHours(
            PublishDate::fromDateTimeString($publishDate),
            $week
        );
    }

    private function weeklyAvailabilityExample(): WeeklyAvailability
    {
        return WeeklyAvailability::create([
            $this->availabilityFactory('2020-01-01', '8:00-16:00'),
            $this->availabilityFactory('2020-01-05', '10:00-16:00'),
            $this->availabilityFactory('2020-01-10', '10:00-20:00'),
        ]);
    }

    /** @test */
    public function weeklyAvailabilitySuccessfullyInstantiatedEmpty()
    {
        $this->assertInstanceOf(
            WeeklyAvailability::class,
            WeeklyAvailability::create()
        );
    }

    /** @test */
    public function weeklyAvailabilitySuccessfullyInstantiatedWithAvailability()
    {
        $this->assertInstanceOf(
            WeeklyAvailability::class,
            WeeklyAvailability::create([
                $this->availabilityFactory('2020-01-01', '8:00-16:00'),
                $this->availabilityFactory('2020-03-01', '10:00-16:00'),
                $this->availabilityFactory('2020-05-01', '10:00-20:00'),
            ])
        );
    }

    /** @test */
    public function availabilityForDateReturnsProperAvailabilityForDateSameAsPublishDate()
    {
        $weeklyAvailability = $this->weeklyAvailabilityExample();

        $lookupDate = DateTimeImmutable::createFromFormat('Y-m-d', '2020-01-01');
        $lookupTimeRange = TimeRange::fromString('8:00-10:00');
        $availability = $weeklyAvailability->openingHoursForDate($lookupDate);

        $this->assertTrue($availability->isTimeRangeAvailable($lookupDate, $lookupTimeRange));
    }

    /** @test */
    public function availabilityForDateReturnsProperAvailabilityForDateInMiddleOfPublishedAvailability()
    {
        $weeklyAvailability = $this->weeklyAvailabilityExample();

        $lookupDate = DateTimeImmutable::createFromFormat('Y-m-d', '2020-01-03');
        $lookupTimeRange = TimeRange::fromString('8:00-10:00');
        $availability = $weeklyAvailability->openingHoursForDate($lookupDate);

        $this->assertTrue($availability->isTimeRangeAvailable($lookupDate, $lookupTimeRange));
    }

    /** @test */
    public function availabilityForDateReturnsProperAvailabilityForDateThatIsLastDayOfPublishedAvailability()
    {
        $weeklyAvailability = $this->weeklyAvailabilityExample();

        $lookupDate = DateTimeImmutable::createFromFormat('Y-m-d', '2020-01-09');
        $lookupTimeRange = TimeRange::fromString('10:00-11:00');
        $availability = $weeklyAvailability->openingHoursForDate($lookupDate);

        $this->assertTrue($availability->isTimeRangeAvailable($lookupDate, $lookupTimeRange));
    }

    /** @test */
    public function availabilityForDateReturnsProperAvailabilityForDateThatTakesWholeAvailableDay()
    {
        $weeklyAvailability = $this->weeklyAvailabilityExample();

        $lookupDate = DateTimeImmutable::createFromFormat('Y-m-d', '2020-01-09');
        $lookupTimeRange = TimeRange::fromString('10:00-16:00');
        $availability = $weeklyAvailability->openingHoursForDate($lookupDate);

        $this->assertTrue($availability->isTimeRangeAvailable($lookupDate, $lookupTimeRange));
    }

    /** @test */
    public function availabilityForDateReturnsProperAvailabilityForDateThatHaveNoTime()
    {
        $weeklyAvailability = $this->weeklyAvailabilityExample();

        $lookupDate = DateTimeImmutable::createFromFormat('Y-m-d', '2020-01-04');
        $lookupTimeRange = TimeRange::fromString('10:00-16:00');
        $availability = $weeklyAvailability->openingHoursForDate($lookupDate);

        $this->assertFalse($availability->isTimeRangeAvailable($lookupDate, $lookupTimeRange));
    }

    /** @test */
    public function availabilityForDateReturnsProperAvailabilityWhenPublishDateOrderIsMixed()
    {
        $weeklyAvailability = WeeklyAvailability::create([
            $this->availabilityFactory('2020-01-02', '15:00-20:00'),
            $this->availabilityFactory('2020-01-01', '8:00-9:00'),
            $this->availabilityFactory('2020-01-10', '9:00-10:00'),
            $this->availabilityFactory('2020-01-05', '11:00-12:00'),
        ]);

        $lookupDate = DateTimeImmutable::createFromFormat('Y-m-d', '2020-01-03');
        $lookupTimeRange = TimeRange::fromString('18:00-20:00');
        $availability = $weeklyAvailability->openingHoursForDate($lookupDate);

        $this->assertTrue($availability->isTimeRangeAvailable($lookupDate, $lookupTimeRange));
    }
}
