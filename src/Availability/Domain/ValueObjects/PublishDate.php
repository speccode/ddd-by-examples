<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain\ValueObjects;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

final class PublishDate
{
    private DateTimeImmutable $publishDate;

    private function __construct(DateTimeInterface $dateTime)
    {
        if ($dateTime instanceof DateTime) {
            $dateTime = DateTimeImmutable::createFromMutable($dateTime);
        }

        $this->publishDate = $dateTime->setTime(0, 0);
    }

    public static function fromDateTimeString(string $date): self
    {
        $date = explode(' ', $date);
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $date[0]);

        if ($date === false) {
            throw new InvalidArgumentException('Invalid date format. Acceptable format is Y-m-d H:i:s or Y-m-d');
        }

        return new self($date);
    }

    public static function fromDateTime(DateTimeInterface $dateTime): self
    {
        return new self($dateTime);
    }

    public function asDateTime(): DateTimeImmutable
    {
        return $this->publishDate;
    }

    public function toString(): string
    {
        return $this->publishDate->format('Y-m-d');
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function equals(PublishDate $other): bool
    {
        return $this->toString() === $other->toString();
    }

    public function isEarlierThan(PublishDate $other): bool
    {
        return $this->publishDate < $other->asDateTime();
    }
}
