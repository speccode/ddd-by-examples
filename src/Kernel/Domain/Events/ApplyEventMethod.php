<?php

namespace Speccode\Kernel\Domain\Events;

trait ApplyEventMethod
{
    public function apply(object $event): void
    {
        $eventName = class_basename($event);
        $applyMethod = 'apply' . $eventName;

        if (method_exists($this, $applyMethod)) {
            $this->$applyMethod($event);
        }
    }
}
