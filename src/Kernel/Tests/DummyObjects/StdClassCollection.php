<?php

namespace Speccode\Kernel\Tests\DummyObjects;

use Speccode\Kernel\Domain\ValueObjects\Collection;
use stdClass;

/**
 * @method stdClass[] getIterator()
 * @method stdClass first()
 * @method stdClass last()
 * @method stdClass get($key)
 * @method stdClass|null find($object)
 */
final class StdClassCollection extends Collection
{
    protected function collectedType(): string
    {
        return stdClass::class;
    }
}
