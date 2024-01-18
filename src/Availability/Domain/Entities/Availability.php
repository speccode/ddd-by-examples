<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain\Entities;

use DateTimeImmutable;
use Orbit\Booking\Domain\ValueObjects\SpaceId;
use Speccode\Availability\Domain\Collections\Blockades;
use Speccode\Availability\Domain\Specifications\MinimumBookingTime;
use Speccode\Availability\Domain\ValueObjects\BlockadeId;
use Speccode\Availability\Domain\ValueObjects\BlockadeType;
use Speccode\Availability\Domain\ValueObjects\ResourceId;
use Speccode\Availability\Domain\ValueObjects\Week;
use Speccode\Kernel\Domain\ValueObjects\DateTimeRange;
use Speccode\Kernel\Domain\ValueObjects\Identities\BatchId;
use Speccode\Kernel\Domain\ValueObjects\Time;
use Speccode\Kernel\Domain\ValueObjects\TimeRange;
use Speccode\Kernel\Domain\Contracts\DealsWithTime;

final class Availability
{
    use DealsWithTime;

    private Blockades $blockades;
    private Week $openingHours;
    private ?TimeRange $opensCloses;

    public function __construct(
        private readonly ResourceId $resourceId,
        private readonly DateTimeImmutable $date,
        Week $openingHours,
        Blockades $blockades,
    )
    {
        $this->setOpeningHours($openingHours);
        $this->setBlockades($blockades);
    }

    public function resourceId(): ResourceId
    {
        return $this->resourceId;
    }

    public function date(): DateTimeImmutable
    {
        return $this->date;
    }

    public function opensClosesTimeRange(): ?TimeRange
    {
        return $this->opensCloses;
    }

    public function opens(): ?Time
    {
        return $this->opensClosesTimeRange()?->start();
    }

    public function closes(): ?Time
    {
        return $this->opensClosesTimeRange()?->end();
    }

    public function blockades(): Blockades
    {
        return $this->blockades;
    }

    public function setBlockades(Blockades $blockades): self
    {
        $this->blockades = $this->addBlockadeForTodayIfNeeded($blockades);

        return $this;
    }

    private function setOpeningHours(Week $openingHours): self
    {
        $this->openingHours = $openingHours;
        $weekday = $openingHours->getWeekdayForDate($this->date());
        $this->opensCloses = $weekday->timeRange();

        return $this;
    }

    public function hasAvailableTime(): bool
    {
        if (! $this->hasOpenCloses()) {
            return false;
        }

        $slots = $this->blockades()->computeAvailableSlots(
            $this->opensCloses
        );
        foreach ($slots as $slot) {
            if (MinimumBookingTime::isSatisfiedBy($slot)) {
                return true;
            }
        }

        return false;
    }

    private function addBlockadeForTodayIfNeeded(Blockades $blockades): Blockades
    {
        if (! $this->isToday($this->date())
            || ! $this->hasOpenCloses()
            || (
                $this->isToday($this->date())
                && $this->opens()?->isLaterOrEqual(Time::fromDateTime($this->now()))
            )) {
            return $blockades;
        }

        $blockadeStartsAt = $this->opens();
        $blockadeDateTimeRange = DateTimeRange::fromDateTime(
            $this->now()->setTime(
                $blockadeStartsAt->hourAsInteger(),
                $blockadeStartsAt->minutesAsInteger()
            ),
            $this->now(),
        );

        $blockade = new Blockade(
            BlockadeId::fromString($this->resourceId()->toString()),
            $blockadeDateTimeRange,
            BlockadeType::buffer(),
            BatchId::fromString($this->resourceId()->toString()),
        );

        return $blockades->add($blockade);
    }

    private function hasOpenCloses(): bool
    {
        return ! is_null($this->opens()) && ! is_null($this->closes());
    }
}
