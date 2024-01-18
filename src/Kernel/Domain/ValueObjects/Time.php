<?php

declare(strict_types=1);

namespace Speccode\Kernel\Domain\ValueObjects;

use DateTimeInterface;
use InvalidArgumentException;

final class Time
{
    private int $hour;
    private int $minutes;

    private function __construct(int $hour, int $minutes)
    {
        $this->guardPositiveNumbers($hour);
        $this->guardPositiveNumbers($minutes);

        $this->hour = $hour;
        $this->minutes = $minutes;
    }

    public static function fromString(string $time): self
    {
        $elements = explode(':', $time);

        if (count($elements) !== 2) {
            throw new InvalidArgumentException('Invalid time format.');
        }

        $hour = (int) trim($elements[0]);
        $minutes = (int) trim($elements[1]);

        return new self($hour, $minutes);
    }

    public static function fromInteger(int $time): self
    {
        $hour = (int) substr((string) $time, 0, strlen((string) $time) - 2);
        $minutes = (int) substr((string) $time, -2);

        return self::fromIntegers($hour, $minutes);
    }

    public static function fromIntegers(int $hour, int $minutes): self
    {
        return new self($hour, $minutes);
    }

    public static function fromFloat(float $time): self
    {
        $hour = (int) $time;
        $minutes = (int) (fmod($time, 1) * 60);

        return new self($hour, (int) $minutes);
    }

    public static function fromDateTime(DateTimeInterface $date): self
    {
        return self::fromString($date->format('H:i'));
    }

    public static function fromMinutes(int $minutes): self
    {
        $hours = (int) floor(($minutes / 60));
        $minutes = $minutes - $hours * 60;

        return new self($hours, $minutes);
    }

    public function asMinutes(): int
    {
        return $this->hourAsInteger() * 60 + $this->minutesAsInteger();
    }

    public function toString(): string
    {
        return sprintf('%02d:%02d', $this->hour, $this->minutes);
    }

    public function asFloat(): float
    {
        return (float) ($this->hour + (($this->minutes / 60) * 100) / 100);
    }

    public function asInteger(): int
    {
        return (int) sprintf('%d%02d', $this->hour, $this->minutes);
    }

    public function hourAsInteger(): int
    {
        return $this->hour;
    }

    public function minutesAsInteger(): int
    {
        return $this->minutes;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function isEarlierThan(Time $other): bool
    {
        return $this->asFloat() < $other->asFloat();
    }

    public function isEarlierOrEqual(Time $other): bool
    {
        return $this->asFloat() <= $other->asFloat();
    }

    public function isLaterThan(Time $other): bool
    {
        return $this->asFloat() > $other->asFloat();
    }

    public function isLaterOrEqual(Time $other): bool
    {
        return $this->asFloat() >= $other->asFloat();
    }

    public function equals(Time $other): bool
    {
        return $this->asFloat() === $other->asFloat();
    }

    public function sub(Time $other): Time
    {
        $result = $this->asMinutes() - $other->asMinutes();
        if ($result < 0) {
            $result = 0;
        }

        return Time::fromMinutes($result);
    }

    public function add(Time $other): Time
    {
        return self::fromMinutes($this->asMinutes() + $other->asMinutes());
    }

    public function notEquals(Time $other): bool
    {
        return ! $this->equals($other);
    }

    private function guardPositiveNumbers(int $candidate)
    {
        if ($candidate < 0) {
            throw new InvalidArgumentException('Given hour or minute MUST BE a positive value.');
        }
    }
}
