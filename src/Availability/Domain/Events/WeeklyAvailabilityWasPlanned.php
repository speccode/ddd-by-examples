<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain\Events;

use Speccode\Kernel\Domain\Events\DomainEvent;

class WeeklyAvailabilityWasPlanned implements DomainEvent
{
    public string $resourceId;
    public string $publishDate;
    public array $week;

    public function __construct(string $resourceId, string $publishDate, array $week)
    {
        $this->publishDate = $publishDate;
        $this->week = $week;
        $this->resourceId = $resourceId;
    }

    public function streamId(): string
    {
        return $this->resourceId;
    }
}
