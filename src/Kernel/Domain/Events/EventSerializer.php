<?php

declare(strict_types=1);

namespace Speccode\Kernel\Domain\Events;

interface EventSerializer
{
    public function serialize(object $event): string;

    public function deserialize(string $eventClass, string $eventId, string $data): object;
}
