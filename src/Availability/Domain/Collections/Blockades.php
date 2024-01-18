<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain\Collections;

use ArrayIterator;
use Closure;
use Speccode\Availability\Domain\Entities\Blockade;
use Speccode\Availability\Domain\ValueObjects\BlockadeType;
use Speccode\Kernel\Domain\ValueObjects\Collection;
use Speccode\Kernel\Domain\ValueObjects\Time;
use Speccode\Kernel\Domain\ValueObjects\TimeRange;
use Speccode\Kernel\Domain\ValueObjects\TimeRanges;

/**
 * @method Blockade get(string $blockadeId)
 * @method Blockade first()
 * @method Blockade last()
 * @method ArrayIterator|Blockade[] getIterator()
 * @method static self create(array $items = [])
 */
final class Blockades extends Collection
{
    public function sortedTimeRanges(): TimeRanges
    {
        return TimeRanges::create(
            $this->mapToArray(fn (Blockade $blockade) => $blockade->timeRange()),
        )->sorted();
    }

    /**
     * @return TimeRange[]
     * @throws \Exception
     */
    public function computeAvailableSlots(TimeRange $openingHoursTimeRange): array
    {
        if ($openingHoursTimeRange->length()->isEarlierOrEqual(
            $this->length(),
        )) {
            return [];
        }

        if ($this->isEmpty()) {
            return [$openingHoursTimeRange];
        }

        $blockadeTimeRanges = $this->sortedTimeRanges()->combine();
        $slots = [];

        if (! $blockadeTimeRanges->first()->start()->isEarlierOrEqual($openingHoursTimeRange->start())) {
            $slots[] = TimeRange::fromTime(
                $openingHoursTimeRange->start(),
                $blockadeTimeRanges->first()->start(),
            );
        }

        $prevSlotEnds = null;
        foreach ($blockadeTimeRanges as $blockadeTimeRange) {
            if (is_null($prevSlotEnds)) {
                $prevSlotEnds = $blockadeTimeRange->end();

                continue;
            }

            $nextSlotStarts = $blockadeTimeRange->start();
            if ($prevSlotEnds->isEarlierThan($nextSlotStarts)) {
                $slots[] = TimeRange::fromTime($prevSlotEnds, $nextSlotStarts);
            }

            $prevSlotEnds = $blockadeTimeRange->end();
        }

        if (! $openingHoursTimeRange->end()->isEarlierOrEqual($blockadeTimeRanges->last()->end())) {
            $slots[] = TimeRange::fromTime(
                $blockadeTimeRanges->last()->end(),
                $openingHoursTimeRange->end(),
            );
        }

        return $slots;
    }

    public function hasNoBookingBlockades(): bool
    {
        return $this->where(function (Blockade $blockade) {
            return $blockade->blockadeType()->equals(BlockadeType::booking());
        })->isEmpty();
    }

    protected function collectedType(): string
    {
        return Blockade::class;
    }

    protected function identifierCallback(): ?Closure
    {
        return function (Blockade $blockade) {
            return $blockade->id()->toString();
        };
    }

    public function hasNoCollidingBlockadesFor(Blockade $blockadeCandidate): bool
    {
        foreach ($this as $blockade) {
            if ($blockadeCandidate->overlaps($blockade)) {
                return false;
            }
        }

        return true;
    }

    public function length(): Time
    {
        $length = Time::fromInteger(0);

        $timeRanges = $this->sortedTimeRanges()->combine();
        foreach ($timeRanges as $timeRange) {
            $length = $length->add($timeRange->length());
        }

        return $length;
    }
}
