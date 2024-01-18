<?php

declare(strict_types=1);

namespace Speccode\Kernel\Tests\DummyObjects;

use Speccode\Kernel\Domain\ValueObjects\Collection;

class FooBarCollection extends Collection
{
    protected function collectedType(): string
    {
        return FooBarAbstract::class;
    }
}
