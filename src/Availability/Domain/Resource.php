<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain;

use DomainException;
use Exception;
use InvalidArgumentException;
use Speccode\Availability\Domain\Collections\Blockades;
use Speccode\Availability\Domain\Collections\WeeklyAvailability;
use Speccode\Availability\Domain\Entities\Blockade;
use Speccode\Availability\Domain\Entities\OpeningHours;
use Speccode\Availability\Domain\Events\AvailableTimeWasBlocked;
use Speccode\Availability\Domain\Events\BlockedTimeWasReleased;
use Speccode\Availability\Domain\Events\TimeBlockRequestWasRejected;
use Speccode\Availability\Domain\Events\WeeklyAvailabilityWasPlanned;
use Speccode\Availability\Domain\Exceptions\BlockadeNotFound;
use Speccode\Availability\Domain\Specifications\BlockedTimeMustBeAvailable;
use Speccode\Availability\Domain\Specifications\BlockedTimeMustNotAlreadyBeBlocked;
use Speccode\Availability\Domain\Specifications\BlockingDateMustBeInFuture;
use Speccode\Availability\Domain\ValueObjects\BlockadeId;
use Speccode\Availability\Domain\ValueObjects\BlockadeType;
use Speccode\Availability\Domain\ValueObjects\PublishDate;
use Speccode\Availability\Domain\ValueObjects\Week;
use Speccode\Kernel\Domain\AggregateRoot;
use Speccode\Kernel\Domain\Specifications\AndSpecification;
use Speccode\Kernel\Domain\ValueObjects\DateTimeRange;
use Speccode\Kernel\Domain\ValueObjects\Identities\BatchId;

final class Resource extends AggregateRoot
{
    private WeeklyAvailability $weeklyAvailability;
    private Blockades $blockades;

    public function boot(): void
    {
        $this->weeklyAvailability = WeeklyAvailability::create();
        $this->blockades = Blockades::create();
    }

    public function planWeeklyAvailability(PublishDate $publishDate, Week $week): void
    {
        if ($publishDate->isEarlierThan(PublishDate::fromDateTime($this->now()))) {
            throw new InvalidArgumentException('PublishDate for planned availability CAN NOT be in past.');
        }

        $this->recordAndApplyThat(new WeeklyAvailabilityWasPlanned(
            $this->aggregateId()->toString(),
            $publishDate->toString(),
            $week->toStringsArray()
        ));
    }

    protected function applyWeeklyAvailabilityWasPlanned(WeeklyAvailabilityWasPlanned $event): void
    {
        $publishDate = PublishDate::fromDateTimeString($event->publishDate);
        $week = Week::fromStringsArray($event->week);

        if ($this->weeklyAvailability->has($publishDate->toString())) {
            $this->weeklyAvailability->get($publishDate->toString())->change($week);
        }

        $this->weeklyAvailability = $this->weeklyAvailability->add(
            new OpeningHours($publishDate, $week)
        );
    }

    public function blockAvailableTime(
        BlockadeId $blockadeId,
        BlockadeType $blockadeType,
        DateTimeRange $blockadeDateTimeRange,
        BatchId $batchId
    ): void
    {
        if ($this->blockades->has($blockadeId->toString())) {
            throw new DomainException('Blockade with given ID already exists');
        }

        $specification = new AndSpecification(
            new BlockingDateMustBeInFuture($this->weeklyAvailability, $this->blockades),
            new BlockedTimeMustBeAvailable($this->weeklyAvailability, $this->blockades),
            new BlockedTimeMustNotAlreadyBeBlocked($this->weeklyAvailability, $this->blockades),
        );

        $blockadeDraft = new Blockade($blockadeId, $blockadeDateTimeRange, $blockadeType, $batchId);

        if ($specification->isNotSatisfiedBy($blockadeDraft)) {
            $this->recordAndApplyThat(
                new TimeBlockRequestWasRejected(
                    $this->aggregateId()->toString(),
                    $blockadeId->toString(),
                    $blockadeType->toString(),
                    $blockadeDateTimeRange->toString(),
                    $batchId->toString(),
                )
            );

            return;
        }

        $this->recordAndApplyThat(
            new AvailableTimeWasBlocked(
                $this->aggregateId()->toString(),
                $blockadeId->toString(),
                $blockadeType->toString(),
                $blockadeDateTimeRange->toString(),
                $batchId->toString()
            )
        );
    }

    protected function applyAvailableTimeWasBlocked(AvailableTimeWasBlocked $event): void
    {
        $this->blockades = $this->blockades->add(new Blockade(
            BlockadeId::fromString($event->blockadeId),
            DateTimeRange::fromString($event->blockadeDateTimeRange),
            BlockadeType::fromString($event->blockadeType),
            BatchId::fromString($event->batchId)
        ));
    }

    public function releaseBlockedTime(BatchId $batchId): void
    {
        $blockadesToRelease = $this->blockades->where(function (Blockade $blockade) use ($batchId) {
            return $blockade->batchId()->equals($batchId);
        });

        if ($blockadesToRelease->isEmpty()) {
            throw new BlockadeNotFound();
        }

        /** @var Blockade $blockade */
        foreach ($blockadesToRelease as $blockade) {
            $this->recordAndApplyThat(new BlockedTimeWasReleased(
                $this->aggregateId()->toString(),
                $blockade->id()->toString(),
                $blockade->batchId()->toString(),
                $blockade->blockadeType()->toString(),
                $blockade->blockadeDateTimeRange()->toString(),
            ));
        }
    }

    protected function applyBlockedTimeWasReleased(BlockedTimeWasReleased $event): void
    {
        $blockade = $this->blockades->get($event->blockadeId);
        $this->blockades = $this->blockades->remove($blockade);
    }
}
