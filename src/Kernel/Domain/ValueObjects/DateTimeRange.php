<?php

declare(strict_types=1);

namespace Speccode\Kernel\Domain\ValueObjects;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

final class DateTimeRange
{
    private const ICAL_DATETIME_FORMAT = 'Ymd\THis';
    private DateTimeImmutable $date;
    private TimeRange $timeRange;

    private function __construct(DateTimeInterface $date, TimeRange $timeRange)
    {
        $this->date = $date->setTime($timeRange->start()->hourAsInteger(), $timeRange->start()->minutesAsInteger());
        $this->timeRange = $timeRange;
    }

    public static function fromString(string $dateTimeRange): self
    {
        $elements = explode(' ', $dateTimeRange);

        if (count($elements) !== 2) {
            throw new InvalidArgumentException('Invalid string format for creating DateTimeRange');
        }

        return new self(
            DateTimeImmutable::createFromFormat('Y-m-d', $elements[0]),
            TimeRange::fromString($elements[1])
        );
    }

    public static function fromDateTime(DateTimeInterface $start, DateTimeInterface $end): self
    {
        if ($start->format('Y-m-d') !== $end->format('Y-m-d')) {
            throw new InvalidArgumentException('DateTimeRange MUST BE created from same day.');
        }

        return new self(
            $start,
            TimeRange::fromTimeStrings(
                $start->format('H:i'),
                $end->format('H:i')
            )
        );
    }

    public function toString(): string
    {
        return $this->date->format('Y-m-d') . ' ' . $this->timeRange->toString();
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function timeRange(): TimeRange
    {
        return $this->timeRange;
    }

    public function date(): DateTimeImmutable
    {
        return $this->date;
    }

    public function isAfter(DateTimeImmutable $date): bool
    {
        return $this->date > $date;
    }

    public function isBefore(DateTimeImmutable $date): bool
    {
        return ! $this->isAfter($date);
    }

    public function endsBefore(DateTimeInterface $dateTime): bool
    {
        return $this->endsAt() <= $dateTime;
    }

    public function isAfterWithTolerance(int $minutes, DateTimeImmutable $date): bool
    {
        return ! $this->isBeforeWithTolerance($minutes, $date);
    }

    public function isBeforeWithTolerance(int $minutes, DateTimeImmutable $date): bool
    {
        if ($this->isAfter($date)) {
            return false;
        }

        if ($minutes < 0) {
            throw new InvalidArgumentException('Minutes must be equal or greater than zero.');
        }

        $minutesDiff = floor(((int) $this->date->format('U') - (int) $date->format('U')) / 60);

        return -$minutes >= $minutesDiff;
    }

    public function startsAt(): DateTimeImmutable
    {
        return $this->date->setTime(
            $this->timeRange->start()->hourAsInteger(),
            $this->timeRange->start()->minutesAsInteger()
        );
    }

    public function endsAt(): DateTimeImmutable
    {
        return $this->date->setTime(
            $this->timeRange->end()->hourAsInteger(),
            $this->timeRange->end()->minutesAsInteger()
        );
    }

    public function isSameDayAs(DateTimeInterface $dateTime): bool
    {
        return $this->date->format('Ymd') === $dateTime->format('Ymd');
    }

    public function totalMinutes(): int
    {
        return (int) ($this->endsAt()->getTimestamp() - $this->startsAt()->getTimestamp()) / 60;
    }

    public function asICalString($uuid = ''): string
    {        //20200924T110000
        $startCal = $this->startsAt()->format(self::ICAL_DATETIME_FORMAT);
        $endCal = $this->endsAt()->format(self::ICAL_DATETIME_FORMAT);

        return 'BEGIN:VEVENT
DTSTART;TZID=Europe/Oslo:' . $startCal . '
DTEND;TZID=Europe/Oslo:' . $endCal . '
X-OP-ENTRY-STATE:convenience
UUID:' . $uuid . '
END:VEVENT';
    }

    public static function fromICalString(string $iCalString): self
    {
        preg_match(
            '/DTSTART;TZID=.*:(?P<dtstart>\d{8}T\d{6}).*DTEND;TZID=.*:(?P<dtend>\d{8}T\d{6})/imsU',
            $iCalString,
            $result
        );

        if (! isset($result['dtstart']) || ! isset($result['dtend'])) {
            throw new InvalidArgumentException('Invalid iCal format.');
        }

        $dateTimeStart = DateTimeImmutable::createFromFormat(self::ICAL_DATETIME_FORMAT, $result['dtstart']);
        $dateTimeEnd = DateTimeImmutable::createFromFormat(self::ICAL_DATETIME_FORMAT, $result['dtend']);

        return self::fromDateTime($dateTimeStart, $dateTimeEnd);
    }
}
