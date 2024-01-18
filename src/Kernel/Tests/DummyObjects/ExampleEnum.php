<?php

declare(strict_types=1);

namespace Speccode\Kernel\Tests\DummyObjects;

use Speccode\Kernel\Domain\ValueObjects\Enum;

/**
 * @method static self KEY_ONE()
 * @method static self KEY_TWO()
 * @method static self KEY_THREE()
 */
final class ExampleEnum extends Enum
{
    private const KEY_ONE = 'value 1';
    private const KEY_TWO = 'value 2';
    private const KEY_THREE = 3;
}
