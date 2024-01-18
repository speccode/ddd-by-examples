<?php

namespace Speccode\Kernel\Tests\DummyObjects;

use Closure;
use Speccode\Kernel\Domain\ValueObjects\Collection;
use stdClass;

/**
 * @method stdClass[] getIterator()
 * @method stdClass first()
 * @method stdClass last()
 * @method stdClass get($key)
 * @method stdClass|null find($object)
 */
final class StdClassCollectionWithIdentifierCallback extends Collection
{
    protected function collectedType(): string
    {
        return stdClass::class;
    }

    protected function identifierCallback(): Closure
    {
        return function (stdClass $item) {
            return $item->id;
        };
    }
}
