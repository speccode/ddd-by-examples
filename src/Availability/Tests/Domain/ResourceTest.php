<?php

declare(strict_types=1);

namespace Speccode\Availability\Tests\Domain;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use InvalidArgumentException;
use Speccode\Availability\Domain\Events\AvailableTimeWasBlocked;
use Speccode\Availability\Domain\Events\BlockedTimeWasReleased;
use Speccode\Availability\Domain\Events\TimeBlockRequestWasRejected;
use Speccode\Availability\Domain\Events\WeeklyAvailabilityWasPlanned;
use Speccode\Availability\Domain\Exceptions\BlockadeNotFound;
use Speccode\Availability\Domain\Resource;
use Speccode\Availability\Domain\ValueObjects\BlockadeId;
use Speccode\Availability\Domain\ValueObjects\BlockadeType;
use Speccode\Availability\Domain\ValueObjects\PublishDate;
use Speccode\Availability\Domain\ValueObjects\Week;
use Speccode\Kernel\Domain\ValueObjects\DateTimeRange;
use Speccode\Kernel\Domain\ValueObjects\Identities\AggregateId;
use Speccode\Kernel\Domain\ValueObjects\Identities\BatchId;
use Speccode\Kernel\Domain\Contracts\DealsWithTime;
use Speccode\Kernel\Tests\Stubs\InMemoryEventStore;
use Speccode\Kernel\Tests\Traits\AggregateRootTest;
use PHPUnit\Framework\TestCase;
use Speccode\Kernel\Tests\Traits\TestsTime;

class ResourceTest extends TestCase
{
    use AggregateRootTest;
    use TestsTime;
    use DealsWithTime;

    private AggregateId $resourceId;

    protected function setUp(): void
    {
        $this->resourceId = AggregateId::fromString('ffffffff-0000-0000-0000-000000000000');
    }

    private function eventStore(): InMemoryEventStore
    {
        return new InMemoryEventStore();
    }

    private function resource(): Resource
    {
        return Resource::retrieve($this->resourceId, $this->eventStore());
    }

    private function availableTimeWasBlocked(AggregateId $aggregateId, string $dateTimeRange): AvailableTimeWasBlocked
    {
        return new AvailableTimeWasBlocked(
            $aggregateId->toString(),
            BlockadeId::generate()->toString(),
            BlockadeType::booking()->toString(),
            DateTimeRange::fromString($dateTimeRange)->toString(),
            BatchId::generate()->toString()
        );
    }

    private function resourceWithAvailability(): Resource
    {
        $resource = $this->resource();
        $resource->apply(
            new WeeklyAvailabilityWasPlanned(
                $resource->aggregateId()->toString(),
                PublishDate::fromDateTimeString('2020-01-01')->toString(),
                Week::fromStringsArray([
                    'monday' => '8:00-16:00',
                    'tuesday' => '8:00-16:00',
                    'wednesday' => '8:00-16:00',
                    'thursday' => '8:00-16:00',
                    'friday' => '8:00-16:00',
                    'saturday' => '8:00-16:00',
                    'sunday' => '8:00-16:00',
                ])->toStringsArray()
            )
        );

        return $resource;
    }

    /** @test */
    public function weeklyAvailabilityWasPlannedIsRecordedWhenPlanningAvailabilityInFuture(): void
    {
        $resource = $this->resource();
        $resource->planWeeklyAvailability(
            PublishDate::fromDateTimeString('2050-01-01'),
            Week::createEmpty()
        );

        $this->assertEventWasRecorded(WeeklyAvailabilityWasPlanned::class, $resource);
    }

    /** @test */
    public function weeklyAvailabilityWasPlannedIsRecordedWhenPlanningAvailabilityFromToday(): void
    {
        $resource = $this->resource();
        $resource->planWeeklyAvailability(
            PublishDate::fromDateTime(new CarbonImmutable()),
            Week::createEmpty()
        );

        $this->assertEventWasRecorded(WeeklyAvailabilityWasPlanned::class, $resource);
    }

    /** @test */
    public function throwExceptionWhenPlanningAvailabilityWithPastDate(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $resource = $this->resource();
        $resource->planWeeklyAvailability(
            PublishDate::fromDateTimeString('2019-01-01'),
            Week::createEmpty()
        );
    }

    /** @test */
    public function availableTimeWasBlockedIsRecordedWhenBlockingAvailableTime(): void
    {
        $resource = $this->resourceWithAvailability();

        $resource->blockAvailableTime(
            BlockadeId::generate(),
            BlockadeType::booking(),
            DateTimeRange::fromString('2050-01-01 10:00-12:00'),
            BatchId::generate()
        );

        $this->assertEventWasRecorded(
            AvailableTimeWasBlocked::class,
            $resource
        );
    }

    /** @test */
    public function timeBlockRequestWasRejectedIsRecordedWhenBlockingTimeInPast(): void
    {
        $resource = $this->resourceWithAvailability();

        $resource->blockAvailableTime(
            BlockadeId::generate(),
            BlockadeType::booking(),
            DateTimeRange::fromString('2020-02-01 10:00-12:00'),
            BatchId::generate()
        );

        $this->assertEventWasRecorded(
            TimeBlockRequestWasRejected::class,
            $resource
        );
    }

    /** @test */
    public function timeBlockRequestWasRejectedIsRecordedWhenBlockingNotAvailableTime(): void
    {
        $resource = $this->resource();

        $resource->blockAvailableTime(
            BlockadeId::generate(),
            BlockadeType::booking(),
            DateTimeRange::fromString('2050-01-01 10:00-12:00'),
            BatchId::generate()
        );

        $this->assertEventWasRecorded(
            TimeBlockRequestWasRejected::class,
            $resource
        );
    }

    /** @test */
    public function timeBlockRequestWasRejectedIsRecordedWhenBlockingTimeThatWasAlreadyBlocked(): void
    {
        $resource = $this->resourceWithAvailability();

        $resource->blockAvailableTime(
            BlockadeId::generate(),
            BlockadeType::booking(),
            DateTimeRange::fromString('2050-01-01 10:00-12:00'),
            BatchId::generate()
        );

        $resource->blockAvailableTime(
            BlockadeId::generate(),
            BlockadeType::booking(),
            DateTimeRange::fromString('2050-01-01 11:00-13:00'),
            BatchId::generate()
        );

        $this->assertEventWasRecorded(
            TimeBlockRequestWasRejected::class,
            $resource
        );
    }

    /** @test */
    public function timeBlockRequestWasRejectedIsRecordedWhenBlockingEndsAfterAvailableTime(): void
    {
        /** @var Resource $resource */
        $resource = $this->resourceWithAvailability();

        $resource->blockAvailableTime(
            BlockadeId::generate(),
            BlockadeType::booking(),
            DateTimeRange::fromString('2050-01-01 12:00-18:00'),
            BatchId::generate()
        );

        $this->assertEventWasRecorded(
            TimeBlockRequestWasRejected::class,
            $resource
        );
    }

    /** @test */
    public function timeBlockRequestWasRejectedIsRecordedWhenBlockingStartsBeforeAvailableTime(): void
    {
        /** @var Resource $resource */
        $resource = $this->resourceWithAvailability();

        $resource->blockAvailableTime(
            BlockadeId::generate(),
            BlockadeType::booking(),
            DateTimeRange::fromString('2050-01-01 06:00-12:00'),
            BatchId::generate()
        );

        $this->assertEventWasRecorded(
            TimeBlockRequestWasRejected::class,
            $resource
        );
    }

    /** @test */
    public function availableTimeWasBlockedIsRecordedWhenBlockingAvailableTimeBackToBackWithOthers(): void
    {
        $resource = $this->resourceWithAvailability();
        $resource->apply(
            $this->availableTimeWasBlocked($resource->aggregateId(), '2050-01-01 8:00-10:00')
        );
        $resource->apply(
            $this->availableTimeWasBlocked($resource->aggregateId(), '2050-01-01 11:00-12:00')
        );

        $resource->blockAvailableTime(
            BlockadeId::generate(),
            BlockadeType::booking(),
            DateTimeRange::fromString('2050-01-01 10:00-11:00'),
            BatchId::generate()
        );

        $this->assertEventWasRecorded(
            AvailableTimeWasBlocked::class,
            $resource
        );
    }

    /** @test */
    public function throwExceptionWhenRealisingNotExistingBlockade(): void
    {
        $this->expectException(BlockadeNotFound::class);

        $resource = $this->resourceWithAvailability();
        $resource->releaseBlockedTime(BatchId::generate());
    }

    /** @test */
    public function blockedTimeWasReleasedIsRecordedWhenRealisingBlockade(): void
    {
        $resource = $this->resourceWithAvailability();
        $batchId = BatchId::generate();
        $resource->apply(
            new AvailableTimeWasBlocked(
                $resource->aggregateId()->toString(),
                BlockadeId::generate()->toString(),
                BlockadeType::booking()->toString(),
                DateTimeRange::fromString('2050-01-01 10:00-12:00')->toString(),
                $batchId->toString()
            )
        );

        $resource->releaseBlockedTime($batchId);

        $this->assertEventWasRecorded(BlockedTimeWasReleased::class, $resource);
    }

    /** @test */
    public function allowsToReleaseBloackedsFromPast(): void
    {
        $resource = $this->resourceWithAvailability();
        $batchId = BatchId::generate();
        $resource->apply(
            new AvailableTimeWasBlocked(
                $resource->aggregateId()->toString(),
                BlockadeId::generate()->toString(),
                BlockadeType::booking()->toString(),
                DateTimeRange::fromString('2020-01-01 10:00-12:00')->toString(),
                $batchId->toString()
            )
        );

        $resource->releaseBlockedTime($batchId);

        $this->assertEventWasRecorded(BlockedTimeWasReleased::class, $resource);
    }

    /** @test */
    public function releasedBlockadeMakeTimeAvailableAgain(): void
    {
        $resource = $this->resourceWithAvailability();
        $batchId = BatchId::generate();
        $resource->apply(
            new AvailableTimeWasBlocked(
                $resource->aggregateId()->toString(),
                BlockadeId::generate()->toString(),
                BlockadeType::booking()->toString(),
                DateTimeRange::fromString('2050-01-01 10:00-12:00')->toString(),
                $batchId->toString()
            )
        );

        $resource->blockAvailableTime(
            BlockadeId::generate(),
            BlockadeType::booking(),
            DateTimeRange::fromString('2050-01-01 10:00-11:00'),
            BatchId::generate()
        );

        $this->assertEventWasRecorded(TimeBlockRequestWasRejected::class, $resource);

        $resource->releaseBlockedTime($batchId);

        $this->assertEventWasRecorded(BlockedTimeWasReleased::class, $resource);

        $resource->blockAvailableTime(
            BlockadeId::generate(),
            BlockadeType::booking(),
            DateTimeRange::fromString('2050-01-01 11:00-14:00'),
            BatchId::generate()
        );

        $this->assertEventWasRecorded(AvailableTimeWasBlocked::class, $resource);
    }

}
