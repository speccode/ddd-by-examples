<?php

declare(strict_types=1);

namespace Speccode\Kernel\Domain\Events;

use Speccode\Kernel\Domain\ValueObjects\Identities\AggregateId;

interface EventStore
{
    public function retrieve(AggregateId $aggregateId, string $aggregateClass): array;

    public function persist(object $event): void;

    public function persistMany(array $events): void;

    public function retrieveAll(): array;
}
