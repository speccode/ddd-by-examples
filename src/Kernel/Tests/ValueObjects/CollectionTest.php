<?php

declare(strict_types=1);

namespace Speccode\Kernel\Tests\ValueObjects;

use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use Speccode\Kernel\Tests\DummyObjects\DateTimeInterfaceCollection;
use Speccode\Kernel\Tests\DummyObjects\FooBarCollection;
use Speccode\Kernel\Tests\DummyObjects\FooBarSolid;
use Speccode\Kernel\Tests\DummyObjects\StdClassCollection;
use Speccode\Kernel\Tests\DummyObjects\StdClassCollectionWithIdentifierCallback;
use PHPUnit\Framework\TestCase;
use stdClass;

class CollectionTest extends TestCase
{
    private array $data = [];

    public function setUp(): void
    {
        $this->data = [
            (object) ['id' => 'foo', 'value' => 'bar'],
            (object) ['id' => 'lorem', 'value' => 'ipsum'],
            (object) ['id' => 'abc', 'value' => 'def'],
        ];
    }

    /** @test */
    public function collectionIsConstructed()
    {
        $this->assertInstanceOf(
            StdClassCollection::class,
            StdClassCollection::create()
        );
    }

    /** @test */
    public function collectionIsConstructedOnlyWithDefinedClass()
    {
        $this->expectException(InvalidArgumentException::class);

        StdClassCollection::create(['1hopethatclassdonotexists']);
    }

    /** @test */
    public function addOnlyCollectedObject()
    {
        $this->expectException(InvalidArgumentException::class);

        $collection = StdClassCollection::create($this->data);
        $collection->add(new DateTime());
    }

    /** @test */
    public function addObjectToCollection()
    {
        $collection = StdClassCollection::create();
        $collection = $collection->add($this->data[0]);
        $collection = $collection->add($this->data[1]);

        $this->assertCount(2, $collection);

        $this->assertSame($this->data[0], $collection->first());
        $this->assertSame($this->data[1], $collection->last());
    }

    /** @test */
    public function addObjectAndAssignKeyByIdentifierCallback()
    {
        $collection = StdClassCollectionWithIdentifierCallback::create($this->data);

        $this->assertSame(
            $this->data[0],
            $collection->get($this->data[0]->id)
        );

        $this->assertSame(
            $this->data[2],
            $collection->get($this->data[2]->id)
        );
    }

    /** @test */
    public function traversable()
    {
        $collection = StdClassCollection::create($this->data);

        $i = 0;
        foreach ($collection as $item) {
            $this->assertInstanceOf(stdClass::class, $item);
            $i++;
        }

        $this->assertSame(count($this->data), $i);
    }

    /** @test */
    public function countable()
    {
        $collection = StdClassCollection::create($this->data);

        $this->assertCount(count($this->data), $collection);
    }

    /** @test */
    public function firstReturnFirstObjectInCollection()
    {
        $collection = StdClassCollection::create($this->data);

        $this->assertSame(
            reset($this->data),
            $collection->first()
        );
    }

    /** @test */
    public function lastReturnLastObjectInCollection()
    {
        $collection = StdClassCollection::create($this->data);

        $this->assertSame(
            end($this->data),
            $collection->last()
        );
    }

    /** @test */
    public function emptyCollectionIsEmpty()
    {
        $collection = StdClassCollection::create();

        $this->assertTrue($collection->isEmpty());
        $this->assertFalse($collection->isNotEmpty());
    }

    /** @test */
    public function emptyCollectionIsNotEmpty()
    {
        $collection = StdClassCollection::create($this->data);

        $this->assertFalse($collection->isEmpty());
        $this->assertTrue($collection->isNotEmpty());
    }

    /** @test */
    public function checkIfCollectionHasKey()
    {
        $collection = StdClassCollectionWithIdentifierCallback::create($this->data);

        $this->assertTrue($collection->has('foo'));
        $this->assertFalse($collection->has('notexistingkey'));
    }

    /** @test */
    public function findWorksOnlyWithCollectedObject()
    {
        $this->expectException(InvalidArgumentException::class);

        $collection = StdClassCollection::create();

        $collection->find(new DateTime());
    }

    /** @test */
    public function findSearchForGivenObjectByIdentifierCallback()
    {
        $collection = StdClassCollectionWithIdentifierCallback::create($this->data);

        $searchFor = $this->data[0];
        $found = $collection->find($searchFor);

        $this->assertSame($searchFor, $found);
    }

    /** @test */
    public function findSearchForGivenObject()
    {
        $collection = StdClassCollection::create($this->data);

        $searchFor = $this->data[0];
        $found = $collection->find($searchFor);

        $this->assertSame($searchFor, $found);
    }

    /** @test */
    public function removeWorksOnlyWithCollectedObject()
    {
        $this->expectException(InvalidArgumentException::class);

        $collection = StdClassCollection::create();

        $collection->remove(new DateTime());
    }

    /** @test */
    public function removeGivenObjectByIdentifierCallback()
    {
        $collection = StdClassCollectionWithIdentifierCallback::create($this->data);

        $toRemove = $this->data[0];
        $collection = $collection->remove($toRemove);

        $this->assertFalse($collection->has($toRemove->id));
    }

    /** @test */
    public function collectionIsImmutable()
    {
        $collectionBefore = StdClassCollection::create($this->data);

        $collectionAfter = $collectionBefore->add((object) ['id' => 'test']);

        $this->assertNotSame($collectionBefore, $collectionAfter);
    }

    /** @test */
    public function mapReturnsNewInstance()
    {
        $originalCollection = StdClassCollection::create($this->data);

        $newCollection = $originalCollection->map(function (stdClass $item) {
            $item->value .= '-hey';

            return $item;
        });

        $this->assertNotEquals($originalCollection, $newCollection);
        $this->assertNotEquals('bar-hey', $originalCollection->first()->value);
        $this->assertEquals('bar-hey', $newCollection->first()->value);
    }

    /** @test */
    public function mapThrowsExceptionIfMappingIntoDifferentType()
    {
        $this->expectException(InvalidArgumentException::class);
        $originalCollection = StdClassCollection::create($this->data);

        $originalCollection->map(function (stdClass $item) {
            return 'hey';
        });
    }

    /** @test */
    public function mapToArrayReturnsAnArrayOfItems()
    {
        $collection = StdClassCollection::create($this->data);

        $array = $collection->mapToArray(function (stdClass $item) {
            return $item->value;
        });

        $this->assertEquals('bar', $array[0]);
        $this->assertEquals('ipsum', $array[1]);
        $this->assertEquals('def', $array[2]);
    }

    /** @test */
    public function whereReturnsNewFilteredCollection()
    {
        $collection = StdClassCollection::create($this->data);

        $filteredCollection = $collection->where(function (stdClass $item) {
            return $item->id === 'foo';
        });

        $this->assertEquals(1, $filteredCollection->count());
        $this->assertEquals('foo', $filteredCollection->first()->id);
    }

    /** @test */
    public function workingWithInterfaces()
    {
        $collection = DateTimeInterfaceCollection::create([]);

        $collection = $collection->add(new DateTime());
        $collection = $collection->add(new DateTimeImmutable());

        $this->assertCount(2, $collection);
    }

    /** @test */
    public function workingWIthAbstractClasses()
    {
        $collection = FooBarCollection::create([]);

        $collection = $collection->add(new FooBarSolid());
        $collection = $collection->add(new FooBarSolid());

        $this->assertCount(2, $collection);
    }
}
