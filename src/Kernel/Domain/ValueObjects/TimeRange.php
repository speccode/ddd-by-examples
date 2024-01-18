<?php

declare(strict_types=1);

namespace Speccode\Kernel\Domain\ValueObjects;

use InvalidArgumentException;

final class TimeRange
{
    private Time $start;
    private Time $end;

    private function __construct(Time $start, Time $end)
    {
        $this->guardStartTimeAndEndTimeHaveToBeDifferent($start, $end);
        $this->guardStartTimeEarlierThanEndTime($start, $end);

        $this->start = $start;
        $this->end = $end;
    }

    public static function fromTime(Time $start, Time $end): self
    {
        return new self($start, $end);
    }

    public static function fromTimeStrings(string $start, string $end): self
    {
        return new self(
            Time::fromString($start),
            Time::fromString($end),
        );
    }

    public static function fromString(string $timeRange): self
    {
        $elements = explode('-', $timeRange);

        if (count($elements) !== 2) {
            throw new InvalidArgumentException('Invalid string format given to parse TimeRange');
        }

        return new self(
            Time::fromString($elements[0]),
            Time::fromString($elements[1]),
        );
    }

    public function toString(): string
    {
        return sprintf(
            '%s-%s',
            $this->start->toString(),
            $this->end->toString(),
        );
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function start(): Time
    {
        return $this->start;
    }

    public function end(): Time
    {
        return $this->end;
    }

    public function length(): Time
    {
        return $this->end->sub($this->start);
    }

    public function hasTime(Time $time): bool
    {
        if (
            $time->asFloat() >= $this->start()->asFloat()
            && $time->asFloat() <= $this->end()->asFloat()
        ) {
            return true;
        }

        return false;
    }

    public function equals(TimeRange $other): bool
    {
        return $this->toString() === $other->toString();
    }

    public function overlaps(TimeRange $otherTimeRange)
    {
        $isTimeInsideTimeRange = fn (Time $time, TimeRange $timeRange) =>
            $time->isLaterOrEqual($timeRange->start()) && $time->isEarlierOrEqual($timeRange->end());

        return $isTimeInsideTimeRange($this->start(), $otherTimeRange)
            || $isTimeInsideTimeRange($this->end(), $otherTimeRange)
            || $isTimeInsideTimeRange($otherTimeRange->start(), $this)
            || $isTimeInsideTimeRange($otherTimeRange->end(), $this);
    }

    public function combine(TimeRange $otherTimeRange): TimeRange
    {
        if (! $this->overlaps($otherTimeRange)) {
            throw new InvalidArgumentException('Time ranges must overlap to be combined.');
        }

        return TimeRange::fromTime(
            $this->start()->isEarlierThan($otherTimeRange->start()) ? $this->start() : $otherTimeRange->start(),
            $this->end()->isLaterThan($otherTimeRange->end()) ? $this->end() : $otherTimeRange->end(),
        );
    }

    private function guardStartTimeAndEndTimeHaveToBeDifferent(Time $startCandidate, Time $endCandidate)
    {
        if ($startCandidate->equals($endCandidate) && $startCandidate->toString() !== '00:00') {
            throw new InvalidArgumentException('Start time and End time MUST BE different');
        }
    }

    private function guardStartTimeEarlierThanEndTime(Time $startCandidate, Time $endCandidate)
    {
        if ($endCandidate->isEarlierThan($startCandidate)) {
            throw new InvalidArgumentException('Start time MUST BE earlier than End time');
        }
    }
}
