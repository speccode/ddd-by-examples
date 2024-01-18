<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain\Services;

use Exception;
use Speccode\Availability\Domain\Exceptions\CouldNotReleaseBlockedTime;
use Speccode\Availability\Domain\Exceptions\ResourceNotFound;
use Speccode\Availability\Domain\Repositories\BatchRepository;
use Speccode\Availability\Domain\Resource;
use Speccode\Availability\Domain\ValueObjects\BlockadeId;
use Speccode\Availability\Domain\ValueObjects\BlockadeType;
use Speccode\Availability\Domain\ValueObjects\PublishDate;
use Speccode\Availability\Domain\ValueObjects\Week;
use Speccode\Kernel\Domain\Events\EventStore;
use Speccode\Kernel\Domain\ValueObjects\DateTimeRange;
use Speccode\Kernel\Domain\ValueObjects\Identities\AggregateId;
use Speccode\Kernel\Domain\ValueObjects\Identities\BatchId;

class DomainAvailabilityService implements AvailabilityService
{
    private EventStore $eventStore;
    private BatchRepository $batchRepository;

    public function __construct(EventStore $eventStore, BatchRepository $batchRepository)
    {
        $this->eventStore = $eventStore;
        $this->batchRepository = $batchRepository;
    }

    public function planWeeklyAvailability(AggregateId $resourceId, PublishDate $publishDate, Week $week): void
    {
        $resource = Resource::retrieve($resourceId, $this->eventStore);
        $resource->planWeeklyAvailability($publishDate, $week);
        $resource->persist();
    }

    public function blockAvailableTime(
        AggregateId $resourceId,
        BlockadeId $blockadeId,
        BlockadeType $blockadeType,
        DateTimeRange $dateTimeRange,
        ?BatchId $batchId = null
    ): void {
        $resource = Resource::retrieve($resourceId, $this->eventStore);
        $resource->blockAvailableTime(
            $blockadeId,
            $blockadeType,
            $dateTimeRange,
            $batchId ?: BatchId::generate()
        );
        $resource->persist();
    }

    public function releaseBlockedTime(AggregateId $resourceId, BatchId $batchId): void
    {
        try {
            $resource = Resource::retrieve($resourceId, $this->eventStore);
            $resource->releaseBlockedTime($batchId);
            $resource->persist();
        } catch (Exception $e) {
            throw new CouldNotReleaseBlockedTime($e->getMessage());
        }
    }

    /**
     * @param BatchId $batchId
     * @throws CouldNotReleaseBlockedTime
     * @throws ResourceNotFound
     */
    public function batchReleaseBlockedTime(BatchId $batchId): void
    {
        $resourceId = $this->batchRepository->findResourceByBatchId($batchId);

        $this->releaseBlockedTime($resourceId, $batchId);
    }

    public function releaseBlockedTimeWithBuffer(AggregateId $resourceId, BatchId $batchId, DateTimeRange $bufferDateTimeRange): void
    {
        $resource = Resource::retrieve($resourceId, $this->eventStore);
        $resource->releaseBlockedTimeWithBuffer($batchId, $bufferDateTimeRange);
        $resource->persist();
    }
}
