<?php

declare(strict_types=1);

namespace Speccode\Kernel\Tests\Traits;

use Speccode\Kernel\Domain\AggregateRoot;

trait AggregateRootTest
{
    public function assertEventWasRecorded(string $eventClassName, AggregateRoot $aggregateRoot): void
    {
        $recorded = false;
        foreach ($aggregateRoot->recordedEvents() as $event) {
            if ($event instanceof $eventClassName) {
                $recorded = true;
            }
        }

        $this->assertTrue($recorded, sprintf('Failed asserting that event %s was recorded', class_basename($eventClassName)));
    }

    public function assertEventWasNotRecorded(string $eventClassName, AggregateRoot $aggregateRoot): void
    {
        $recorded = false;
        foreach ($aggregateRoot->recordedEvents() as $event) {
            if ($event instanceof $eventClassName) {
                $recorded = true;
            }
        }

        $this->assertFalse($recorded, sprintf('Failed asserting that event %s was NOT recorded', class_basename($eventClassName)));
    }
}
