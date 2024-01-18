<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain\Services;

use Speccode\Availability\Domain\ValueObjects\BlockadeId;
use Speccode\Availability\Domain\ValueObjects\BlockadeType;
use Speccode\Availability\Domain\ValueObjects\PublishDate;
use Speccode\Availability\Domain\ValueObjects\Week;
use Speccode\Kernel\Domain\ValueObjects\DateTimeRange;
use Speccode\Kernel\Domain\ValueObjects\Identities\AggregateId;
use Speccode\Kernel\Domain\ValueObjects\Identities\BatchId;

interface AvailabilityService
{
    public function planWeeklyAvailability(AggregateId $resourceId, PublishDate $publishDate, Week $week): void;

    public function blockAvailableTime(
        AggregateId $resourceId,
        BlockadeId $blockadeId,
        BlockadeType $blockadeType,
        DateTimeRange $dateTimeRange,
        ?BatchId $batchId = null
    ): void;

    public function releaseBlockedTime(AggregateId $resourceId, BatchId $batchId): void;

    public function batchReleaseBlockedTime(BatchId $batchId): void;

    public function releaseBlockedTimeWithBuffer(AggregateId $resourceId, BatchId $batchId, DateTimeRange $bufferDateTimeRange): void;
}
