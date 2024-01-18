<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain\Events;

use Speccode\Kernel\Domain\Events\DomainEvent;

class BlockedTimeWasReleased implements DomainEvent
{
    public string $resourceId;
    public string $blockadeId;
    public string $batchId;
    public string $blockadeDateTimeRange;
    public string $blockadeType;

    public function __construct(string $resourceId, string $blockadeId, string $batchId, string $blockadeType, string $blockadeDateTimeRange)
    {
        $this->resourceId = $resourceId;
        $this->blockadeId = $blockadeId;
        $this->batchId = $batchId;
        $this->blockadeDateTimeRange = $blockadeDateTimeRange;
        $this->blockadeType = $blockadeType;
    }

    public function streamId(): string
    {
        return $this->resourceId;
    }
}
