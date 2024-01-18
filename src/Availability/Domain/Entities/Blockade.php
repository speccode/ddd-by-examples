<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain\Entities;

use DateTimeImmutable;
use Speccode\Availability\Domain\ValueObjects\BlockadeId;
use Speccode\Availability\Domain\ValueObjects\BlockadeType;
use Speccode\Kernel\Domain\ValueObjects\DateTimeRange;
use Speccode\Kernel\Domain\ValueObjects\Identities\BatchId;
use Speccode\Kernel\Domain\ValueObjects\TimeRange;

final class Blockade
{
    private BlockadeId $blockadeId;
    private DateTimeRange $blockadeDateTimeRange;
    private BlockadeType $blockadeType;
    private BatchId $batchId;

    public function __construct(
        BlockadeId $blockadeId,
        DateTimeRange $blockadeDateTimeRange,
        BlockadeType $blockadeType,
        BatchId $batchId
    ) {
        $this->blockadeId = $blockadeId;
        $this->blockadeDateTimeRange = $blockadeDateTimeRange;
        $this->blockadeType = $blockadeType;
        $this->batchId = $batchId;
    }

    public function id(): BlockadeId
    {
        return $this->blockadeId;
    }

    public function batchId(): BatchId
    {
        return $this->batchId;
    }

    public function startsAt(): DateTimeImmutable
    {
        return $this->blockadeDateTimeRange->startsAt();
    }

    public function endsAt(): DateTimeImmutable
    {
        return $this->blockadeDateTimeRange->endsAt();
    }

    public function blockadeDateTimeRange(): DateTimeRange
    {
        return $this->blockadeDateTimeRange;
    }

    public function blockadeType(): BlockadeType
    {
        return $this->blockadeType;
    }

    public function timeRange(): TimeRange
    {
        return $this->blockadeDateTimeRange->timeRange();
    }

    public function overlaps(Blockade $other): bool
    {
        if ($this->startsAt() >= $other->startsAt() && $this->startsAt() < $other->endsAt()) {
            return true;
        }

        if ($this->endsAt() > $other->startsAt() && $this->endsAt() <= $other->endsAt()) {
            return true;
        }

        return false;
    }
}
