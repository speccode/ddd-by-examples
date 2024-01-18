<?php

declare(strict_types=1);

namespace Speccode\Kernel\Tests\DummyObjects;

use DateTimeInterface;
use Speccode\Kernel\Domain\ValueObjects\Collection;

class DateTimeInterfaceCollection extends Collection
{
    protected function collectedType(): string
    {
        return DateTimeInterface::class;
    }
}
