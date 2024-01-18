<?php

declare(strict_types=1);

namespace Speccode\Kernel\Domain;

use Speccode\Kernel\Domain\Events\ApplyEventMethod;
use Speccode\Kernel\Domain\Events\EventStore;
use Speccode\Kernel\Domain\ValueObjects\Identities\AggregateId;
use Speccode\Kernel\Domain\Contracts\DealsWithTime;

abstract class AggregateRoot
{
    use ApplyEventMethod;
    use DealsWithTime;

    private AggregateId $aggregateId;
    private EventStore $eventStore;
    private array $recordedEvents = [];

    final private function __construct(EventStore $eventStore)
    {
        $this->eventStore = $eventStore;
        $this->boot();
    }

    public function boot(): void
    {
    }

    /**
     * @param AggregateId $aggregateId
     * @param EventStore $eventStore
     * @return static
     */
    public static function retrieve(AggregateId $aggregateId, EventStore $eventStore): self
    {
        $aggregateRoot = new static($eventStore);
        $aggregateRoot->aggregateId = $aggregateId;
        $aggregateRoot->reconstituteFromEvents();

        return $aggregateRoot;
    }

    /**
     * @param EventStore $eventStore
     * @return static
     */
    public static function new(EventStore $eventStore): self
    {
        return static::retrieve(AggregateId::generate(), $eventStore);
    }

    public function aggregateId(): AggregateId
    {
        return $this->aggregateId;
    }

    private function reconstituteFromEvents(): void
    {
        $events = $this->eventStore->retrieve(
            $this->aggregateId,
            static::class,
        );

        foreach ($events as $event) {
            $this->apply($event);
        }
    }

    public function persist(): void
    {
        $this->eventStore->persistMany($this->recordedEvents);
        $this->recordedEvents = [];
    }

    /**
     * @param object $event
     * @return static
     */
    public function recordThat(object $event): self
    {
        $this->recordedEvents[] = $event;

        return $this;
    }

    /**
     * @param object $event
     * @return static
     */
    public function recordAndApplyThat(object $event): self
    {
        $this->recordThat($event);
        $this->apply($event);

        return $this;
    }

    public function recordedEvents(): array
    {
        return $this->recordedEvents;
    }
}
