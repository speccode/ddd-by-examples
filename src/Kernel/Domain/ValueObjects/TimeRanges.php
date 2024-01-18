<?php

declare(strict_types=1);

namespace Speccode\Kernel\Domain\ValueObjects;

use ArrayIterator;

/**
 * @method TimeRange first()
 * @method TimeRange last()
 * @method ArrayIterator|TimeRange[] getIterator()
 * @method static self create(array $items = [])
 */
class TimeRanges extends Collection
{
    public function combine(): TimeRanges
    {
        return self::create(array_reduce(
            $this->toArray(),
            function (array $reducedTimeRanges, TimeRange $timeRange) {
                if (empty($reducedTimeRanges)) {
                    return [$timeRange];
                }

                $combinedTimeRanges = [];

                $timeRangeHasBeenAdded = false;
                /** @var TimeRange $previousTimeRange */
                foreach ($reducedTimeRanges as $previousTimeRange) {
                    if ($previousTimeRange->overlaps($timeRange)) {
                        $combinedTimeRanges[] = $previousTimeRange->combine($timeRange);
                        $timeRangeHasBeenAdded = true;

                        break;
                    } else {
                        $combinedTimeRanges[] = $previousTimeRange;
                    }
                }

                if (! $timeRangeHasBeenAdded) {
                    $combinedTimeRanges[] = $timeRange;
                }

                return $combinedTimeRanges;
            },
            [],
        ));
    }

    /**
     * @return string[]
     */
    public function toStringsArray(): array
    {
        return $this->mapToArray(fn (TimeRange $timeRange) => $timeRange->toString());
    }

    /**
     * @return TimeRange[]
     */
    public function toArray(): array
    {
        return $this->mapToArray(fn (TimeRange $timeRange) => $timeRange);
    }

    public function sorted(): TimeRanges
    {
        return $this->sort(fn (TimeRange $left, TimeRange $right) => $left->start() > $right->start());
    }

    protected function collectedType(): string
    {
        return TimeRange::class;
    }
}
