<?php

namespace Speccode\Kernel\Domain\Events;

interface StorableEvent
{
    public function streamId(): string;
}
