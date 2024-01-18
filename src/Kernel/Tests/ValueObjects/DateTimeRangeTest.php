<?php

declare(strict_types=1);

namespace Speccode\Kernel\Tests\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;
use Speccode\Kernel\Domain\ValueObjects\DateTimeRange;
use PHPUnit\Framework\TestCase;

class DateTimeRangeTest extends TestCase
{
    /** @test */
    public function dateTimeRangeSuccessfullyInstantiatedFromString()
    {
        $this->assertInstanceOf(
            DateTimeRange::class,
            DateTimeRange::fromString('2020-01-01 10:00-12:00')
        );
    }

    /** @test */
    public function dateTimeRangeSuccessfullyInstantiatedFromDateTimeObjects()
    {
        $this->assertInstanceOf(
            DateTimeRange::class,
            DateTimeRange::fromDateTime(
                new DateTimeImmutable('10:00'),
                new DateTimeImmutable('12:00')
            )
        );
    }

    /** @test */
    public function dateTimeRangeSuccessfullyInstantiatedFromICalString(): void
    {
        $this->assertInstanceOf(
            DateTimeRange::class,
            DateTimeRange::fromICalString('BEGIN:VEVENT
    DTSTART;TZID=Europe/Oslo:20210701T090000
    DTEND;TZID=Europe/Oslo:20210701T170000
    X-OP-ENTRY-STATE:convenience
    END:VEVENT
')
        );
    }

    /** @test */
    public function throwExceptionWhenInstantiatedFromInvalidICalString(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->assertInstanceOf(
            DateTimeRange::class,
            DateTimeRange::fromICalString('BEGIN:VEVENT
    DTSTART;TZID=Europe/Oslo:20210701
    DT;TZID=Europe/Oslo:20210701T170000
')
        );
    }

    /** @test */
    public function throwExceptionWhenCreatingDateTimeRangeWithDifferentDays()
    {
        $this->expectException(InvalidArgumentException::class);

        $yesterday = (new DateTimeImmutable('yesterday'))->setTime(10, 0, 0);
        $today = (new DateTimeImmutable())->setTime(12, 0, 0);

        DateTimeRange::fromDateTime($yesterday, $today);
    }

    /**
     * @test
     * @dataProvider invalidDateTimeRangeFormat
     * @param string $dateTimeRange
     */
    public function throwExceptionWhenCreatingFromWrongStringFormat(string $dateTimeRange)
    {
        $this->expectException(InvalidArgumentException::class);

        DateTimeRange::fromString('01.01.2020 12:00');
    }

    public static function invalidDateTimeRangeFormat(): array
    {
        return [
            ['01.01.2020 12:00'],
            ['01.01.2020'],
        ];
    }

    /** @test */
    public function properlyCreatedFromDateTimeRangeString()
    {
        $dateTimeRange = DateTimeRange::fromString('2020-01-01 10:00-12:00');

        $this->assertSame('2020-01-01 10:00-12:00', $dateTimeRange->toString());
    }

    /** @test */
    public function inPastAndInFutureCorrectlyIndicatesFlags()
    {
        $today = new DateTimeImmutable('2020-10-30 12:00:00');

        $dateTimeRangeFuture = DateTimeRange::fromString('2020-10-31 10:00-12:00');
        $this->assertTrue($dateTimeRangeFuture->isAfter($today));

        $dateTimeRangePast = DateTimeRange::fromString('2020-10-29 10:00-12:00');
        $this->assertTrue($dateTimeRangePast->isBefore($today));
    }

    /** @test */
    public function inPastWithToleranceCorrectlyIndicatesFlag()
    {
        $now = new DateTimeImmutable('2020-10-22 14:00:00');
        $startDateTime = $now->modify('-5 minutes');
        $endDateTime = $now->modify('+2 hours');
        $tomorrowStartDateTime = $now->modify('+1 day -2 hours');
        $tomorrowEndDateTime = $tomorrowStartDateTime->modify('+1 hours');

        $dateTimeRange = DateTimeRange::fromDateTime($startDateTime, $endDateTime);
        $futureDateTimeRange = DateTimeRange::fromDateTime($tomorrowStartDateTime, $tomorrowEndDateTime);

        $this->assertTrue($dateTimeRange->isBefore($now));
        $this->assertFalse($dateTimeRange->isBeforeWithTolerance(10, $now));
        $this->assertTrue($dateTimeRange->isBeforeWithTolerance(3, $now));
        $this->assertFalse($futureDateTimeRange->isBeforeWithTolerance(10, $now));
    }

    /** @test */
    public function calculatesTotalMinutes(): void
    {
        $dateTimeRange = DateTimeRange::fromString('2021-02-18 10:00-11:35');

        $this->assertSame(95, $dateTimeRange->totalMinutes());
    }

    /** @test */
    public function endsBeforeCorrectlyIndicatesFlag(): void
    {
        $startDateTime = new DateTimeImmutable('2021-07-01 13:00:00');
        $endDateTime = new DateTimeImmutable('2021-07-01 15:00:00');
        $nowAfterEnd = new DateTimeImmutable('2021-07-01 15:15:00');
        $nowBeforeEnd = new DateTimeImmutable('2021-07-01 14:45:00');

        $dateTimeRange = DateTimeRange::fromDateTime($startDateTime, $endDateTime);

        $this->assertTrue($dateTimeRange->endsBefore($nowAfterEnd));
        $this->assertFalse($dateTimeRange->endsBefore($nowBeforeEnd));
    }

    /** @test */
    public function isSameDayAsCorrectlyIndicatesFlag(): void
    {
        //given
        $dayInPast = new DateTimeImmutable('2021-10-18 11:00:00');
        $sameDay = new DateTimeImmutable('2021-10-19 11:00:00');
        $dayInFuture = new DateTimeImmutable('2021-10-20 11:00:00');
        $dateTimeRange = DateTimeRange::fromString('2021-10-19 13:00-15:00');

        //then
        $this->assertFalse($dateTimeRange->isSameDayAs($dayInPast));
        $this->assertTrue($dateTimeRange->isSameDayAs($sameDay));
        $this->assertFalse($dateTimeRange->isSameDayAs($dayInFuture));
    }
}
