<?php

declare(strict_types=1);

namespace Speccode\Kernel\Tests\Stubs;

use Speccode\Kernel\Domain\Events\EventStore;
use Speccode\Kernel\Domain\Events\StorableEvent;
use Speccode\Kernel\Domain\ValueObjects\Identities\AggregateId;

class InMemoryEventStore implements EventStore
{
    private array $events = [];

    public function retrieve(AggregateId $aggregateId, string $aggregateClass): array
    {
        return $this->events[$aggregateId->toString()] ?? [];
    }

    public function persist(object $event): void
    {
        $this->persistMany([$event]);
    }

    /**
     * @param StorableEvent[] $events
     */
    public function persistMany(array $events): void
    {
        foreach ($events as $event) {
            if ($this->streamDoNotExists($event->streamId())) {
                $this->events[$event->streamId()] = [];
            }

            $this->events[$event->streamId()][] = $event;
        }
    }

    private function streamDoNotExists(string $streamId): bool
    {
        return ! isset($this->events[$streamId]);
    }

    public function retrieveAll(): array
    {
        return $this->events;
    }
}
