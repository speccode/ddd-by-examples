<?php

declare(strict_types=1);

namespace Speccode\Availability\Tests\Domain\ValueObjects;

use InvalidArgumentException;
use Speccode\Availability\Domain\ValueObjects\BlockadeType;
use PHPUnit\Framework\TestCase;

class BlockadeTypeTest extends TestCase
{
    /** @test */
    public function blockTypeSuccessfullyInstantiatedFromString()
    {
        $this->assertInstanceOf(
            BlockadeType::class,
            BlockadeType::fromString('exception')
        );
    }

    /** @test */
    public function blockTypeSuccessfullyInstantiatedFromStaticNamedMethod()
    {
        $this->assertInstanceOf(
            BlockadeType::class,
            BlockadeType::exception()
        );
    }

    /** @test */
    public function throwExceptionWhenInstantiatingFromStringWithWrongType()
    {
        $this->expectException(InvalidArgumentException::class);

        BlockadeType::fromString('foobar');
    }

    /** @test */
    public function throwExceptionWhenInstantiatingFromStaticNamedMethodWithWrongType()
    {
        $this->expectException(InvalidArgumentException::class);

        BlockadeType::foobar();
    }

    /** @test */
    public function equalsReturnTrueWhenComparedWithSameBlockType()
    {
        $typeA = BlockadeType::exception();
        $typeB = BlockadeType::exception();
        $typeC = BlockadeType::reservation();

        $this->assertTrue($typeA->equals($typeB));
        $this->assertFalse($typeA->notEquals($typeB));
        $this->assertFalse($typeA->equals($typeC));
        $this->assertTrue($typeA->notEquals($typeC));
    }
}
