<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain\ValueObjects;

use InvalidArgumentException;
use Speccode\Kernel\Domain\ValueObjects\DateTimeRange;
use Speccode\Kernel\Domain\ValueObjects\TimeRange;

/**
 * @method static Weekday monday(string|TimeRange $timeRange = null)
 * @method static Weekday tuesday(string|TimeRange $timeRange = null)
 * @method static Weekday wednesday(string|TimeRange $timeRange = null)
 * @method static Weekday thursday(string|TimeRange $timeRange = null)
 * @method static Weekday friday(string|TimeRange $timeRange = null)
 * @method static Weekday saturday(string|TimeRange $timeRange = null)
 * @method static Weekday sunday(string|TimeRange $timeRange = null)
 */
final class Weekday
{
    private static array $weekdaysMap = [
        1 => 'monday',
        2 => 'tuesday',
        3 => 'wednesday',
        4 => 'thursday',
        5 => 'friday',
        6 => 'saturday',
        7 => 'sunday',
    ];

    private int $weekday;
    private ?TimeRange $timeRange;

    private function __construct(int $weekday, ?TimeRange $timeRange)
    {
        $this->guardWeekdayNumberExistsInMap($weekday);

        $this->weekday = $weekday;
        $this->timeRange = $timeRange;
    }

    public static function fromString(string $weekdayName, string $timeRange = null): self
    {
        $weekdayNumber = self::getWeekdayNumberByName($weekdayName);

        if ($timeRange) {
            $timeRange = TimeRange::fromString($timeRange);
        } else {
            $timeRange = null;
        }

        return new self($weekdayNumber, $timeRange);
    }

    public static function fromDateTimeRange(DateTimeRange $dateTimeRange): self
    {
        return new self(
            self::getWeekdayNumberByName(mb_strtolower($dateTimeRange->date()->format('l'))),
            $dateTimeRange->timeRange()
        );
    }

    public function toString(): string
    {
        return self::$weekdaysMap[$this->weekday];
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function timeRange(): ?TimeRange
    {
        return $this->timeRange;
    }

    public function isEmpty(): bool
    {
        return is_null($this->timeRange);
    }

    private function guardWeekdayNumberExistsInMap(int $weekdayNumberCandidate)
    {
        if (! array_key_exists($weekdayNumberCandidate, self::$weekdaysMap)) {
            throw new InvalidArgumentException('Given weekday number is invalid.');
        }
    }

    private static function getWeekdayNumberByName(string $name): int
    {
        $weekdayNumber = array_search($name, self::$weekdaysMap);

        if ($weekdayNumber === false) {
            throw new InvalidArgumentException('Weekday with given name is invalid.');
        }

        return $weekdayNumber;
    }

    public static function __callStatic(string $name, array $arguments): self
    {
        $weekday = self::getWeekdayNumberByName($name);
        $timeRange = null;

        if (isset($arguments[0])) {
            if ($arguments[0] instanceof TimeRange) {
                $timeRange = $arguments[0];
            }

            if (is_string($arguments[0])) {
                $timeRange = TimeRange::fromString($arguments[0]);
            }
        }

        return new self($weekday, $timeRange);
    }

    public function fitIn(Weekday $other): bool
    {
        if ($this->toString() !== $other->toString()) {
            throw new InvalidArgumentException('You can only check if same weekdays will fit');
        }

        if (is_null($other->timeRange())) {
            return false;
        }

        $thisStart = $this->timeRange()->start();
        $thisEnd = $this->timeRange()->end();
        $otherStart = $other->timeRange()->start();
        $otherEnd = $other->timeRange()->end();

        return match (true) {
            $thisStart->isEarlierThan($otherStart) && $thisStart->notEquals($otherStart),
                $thisEnd->isLaterThan($otherEnd) && $thisEnd->notEquals($otherEnd),
                $thisStart->equals($otherEnd) || $thisStart->isLaterThan($otherEnd),
                $thisEnd->equals($otherStart) || $thisEnd->isEarlierThan($otherStart) => false,
            default => true,
        };
    }

    public function equals(Weekday $other): bool
    {
        return $this->toString() === $other->toString()
            && (
                (is_null($this->timeRange()) && is_null($other->timeRange()))
                || (! is_null($other->timeRange()) && $this->timeRange()?->equals($other->timeRange()))
            );
    }
}
