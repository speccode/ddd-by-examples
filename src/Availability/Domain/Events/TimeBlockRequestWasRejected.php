<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain\Events;

use Speccode\Kernel\Domain\Events\DomainEvent;

class TimeBlockRequestWasRejected implements DomainEvent
{
    public string $resourceId;
    public string $blockadeId;
    public string $blockadeType;
    public string $blockadeDateTimeRange;
    public string $batchId;

    public function __construct(string $resourceId, string $blockadeId, string $blockadeType, string $blockadeDateTimeRange, string $batchId)
    {
        $this->resourceId = $resourceId;
        $this->blockadeId = $blockadeId;
        $this->blockadeType = $blockadeType;
        $this->blockadeDateTimeRange = $blockadeDateTimeRange;
        $this->batchId = $batchId;
    }

    public function streamId(): string
    {
        return $this->resourceId;
    }
}
