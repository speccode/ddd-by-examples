<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain\ValueObjects;

use ArrayIterator;
use Closure;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Speccode\Kernel\Domain\ValueObjects\Collection;

/**
 * @method Weekday monday()
 * @method Weekday tuesday()
 * @method Weekday wednesday()
 * @method Weekday thursday()
 * @method Weekday friday()
 * @method Weekday saturday()
 * @method Weekday sunday()
 * @method Weekday get(string $weekday)
 * @method Weekday[]|ArrayIterator getIterator()
 * @property-read Weekday[] $items
 */
final class Week extends Collection
{
    protected function collectedType(): string
    {
        return Weekday::class;
    }

    protected function identifierCallback(): ?Closure
    {
        return function (Weekday $weekday) {
            return $weekday->toString();
        };
    }

    /**
     * @param Weekday[] $weekdays
     * @return static
     */
    public static function create(array $weekdays = []): self
    {
        foreach ($weekdays as $weekday) {
            if (! $weekday instanceof Weekday) {
                throw new InvalidArgumentException('When creating Week you should only use Weekday objects.');
            }
        }

        $week = parent::create([
            Weekday::monday(),
            Weekday::tuesday(),
            Weekday::wednesday(),
            Weekday::thursday(),
            Weekday::friday(),
            Weekday::saturday(),
            Weekday::sunday(),
        ]);

        if (empty($weekdays)) {
            return $week;
        }

        foreach ($weekdays as $weekday) {
            $week = $week->replace($weekday);
        }

        return $week;
    }

    public static function createEmpty(): self
    {
        return static::create();
    }

    public function add($value): self
    {
        throw new DomainException('You CAN NOT add another weekdays to existing week');
    }

    public function remove($value): self
    {
        throw new DomainException('You CAN NOT remove weekdays from week');
    }

    /**
     * @param Weekday $weekday
     * @return $this
     */
    public function replace($weekday): self
    {
        if (! $weekday instanceof Weekday) {
            throw new InvalidArgumentException('Given argument should be instance of Weekday.');
        }

        if ($this->has($weekday->toString())) {
            return parent::add($weekday);
        }

        throw new InvalidArgumentException('Trying to replace Weekday that do not exists in given Week.');
    }

    /**
     * @param Weekday[] $weekdays
     * @return static
     */
    public static function fromArray(array $weekdays): self
    {
        return self::create($weekdays);
    }

    public static function fromStringsArray(array $stringWeekdays): self
    {
        $weekdays = [];
        foreach ($stringWeekdays as $weekday => $timeRange) {
            $weekdays[] = Weekday::fromString($weekday, $timeRange);
        }

        return self::create($weekdays);
    }

    public function toStringsArray(): array
    {
        $weekArray = [];

        foreach ($this as $weekday) {
            $weekArray[$weekday->toString()] = (string) $weekday->timeRange();
        }

        return $weekArray;
    }

    public function asTimesArray(): array
    {
        $weekArray = [];

        foreach ($this as $weekday) {
            $times = null;
            if ($weekday->timeRange()) {
                $times = [
                    'opens' => $weekday->timeRange()->start()->toString(),
                    'closes' => $weekday->timeRange()->end()->toString(),
                ];
            }
            $weekArray[$weekday->toString()] = $times;
        }

        return $weekArray;
    }

    public function __call(string $name, array $arguments): Weekday
    {
        if ($this->has($name)) {
            return $this->get($name);
        }

        throw new InvalidArgumentException('Wrong weekday name.');
    }

    public function hasWeekendAvailable(): bool
    {
        return ! $this->saturday()->isEmpty() || ! $this->sunday()->isEmpty();
    }

    public function getWeekdayForDate(DateTimeImmutable $date): Weekday
    {
        $day = mb_strtolower($date->format('l'));

        return $this->get($day);
    }
}
