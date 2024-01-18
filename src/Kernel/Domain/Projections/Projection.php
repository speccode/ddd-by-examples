<?php

declare(strict_types=1);

namespace Speccode\Kernel\Domain\Projections;

interface Projection
{
    public function apply(object $event): void;

    public function reset();
}
