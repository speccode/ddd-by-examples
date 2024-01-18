<?php

declare(strict_types=1);

namespace Speccode\Kernel\Domain\ValueObjects;

use ArrayIterator;
use BadMethodCallException;
use Closure;
use Exception;
use InvalidArgumentException;
use IteratorAggregate;
use OutOfBoundsException;
use ReflectionClass;
use ReflectionException;

abstract class Collection implements IteratorAggregate
{
    private array $items = [];

    final protected function __construct(array $items)
    {
        foreach ($items as $item) {
            $this->guardWorkOnlyWithCollectedClass($item);

            if ($this->identifierCallback()) {
                $this->items[$this->getOffset($item)] = $item;
            } else {
                $this->items[] = $item;
            }
        }
    }

    public static function create(array $items = []): self
    {
        return new static($items);
    }

    /**
     * What type is this a collection of
     */
    abstract protected function collectedType(): string;

    /**
     * Override this if you want to specify a identifier callback to
     * specify what should be used as a key in the collection
     */
    protected function identifierCallback(): ?Closure
    {
        return null;
    }

    public function add($value): self
    {
        $items = $this->items;

        if (is_array($value)) {
            foreach ($value as $item) {
                $this->guardWorkOnlyWithCollectedClass($item);
                $items[] = $item;
            }
        } else {
            $this->guardWorkOnlyWithCollectedClass($value);
            $items[] = $value;
        }

        return new static($items);
    }

    public function find($value)
    {
        $this->guardWorkOnlyWithCollectedClass($value);

        if ($this->identifierCallback() === null) {
            foreach ($this->items as $item) {
                if ($item === $value) {
                    return $item;
                }
            }

            return null;
        }

        $offset = $this->getOffset($value);

        if ($this->has($offset)) {
            return $this->get($offset);
        }

        return null;
    }

    public function get(string $key)
    {
        if ($this->has($key)) {
            return $this->items[$key];
        }

        throw new OutOfBoundsException('Given key do not exists in collection');
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function remove($value): self
    {
        $this->guardWorkOnlyWithCollectedClass($value);

        $offset = $this->getOffset($value);

        $items = $this->items;

        if ($this->has($offset)) {
            unset($items[$offset]);
        }

        return new static($items);
    }

    public function replace($value): self
    {
        if ($this->identifierCallback() === null) {
            throw new BadMethodCallException("The replace() method does not work without an identifier callback.");
        }

        return $this->add($value);
    }

    public function keys(): array
    {
        $keys = [];
        foreach ($this as $key => $item) {
            $keys[] = $key;
        }

        return $keys;
    }

    public function map(callable $callback): self
    {
        $items = $this->mapToArray($callback);

        return new static($items);
    }

    public function mapToArray(callable $callback): array
    {
        $items = [];

        foreach ($this->items as $item) {
            $items[] = $callback(clone $item);
        }

        return $items;
    }

    public function sort(callable $callback): self
    {
        $items = $this->items;
        uasort($items, $callback);

        return new static($items);
    }

    public function where(callable $callback): self
    {
        return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function first()
    {
        if ($this->isEmpty()) {
            throw new Exception('Can\'t fetch first item of an empty collection');
        }

        return reset($this->items);
    }

    public function last()
    {
        if ($this->isEmpty()) {
            throw new Exception('Can\'t fetch last item of an empty collection');
        }

        return end($this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    private function defaultIdentifierCallback(): Closure
    {
        return function () {
            return $this->count();
        };
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    private function getOffset($item): string
    {
        $identifierCallback = $this->identifierCallback() ?? $this->defaultIdentifierCallback();

        return (string) ($identifierCallback)($item);
    }

    private function guardWorkOnlyWithCollectedClass($candidate): void
    {
        try {
            $collectedType = new ReflectionClass($this->collectedType());
            $candidate = new ReflectionClass($candidate);
        } catch (ReflectionException $e) {
            throw new InvalidArgumentException('Could not create reflection class for collected type or candidate.');
        }

        if ($collectedType->getName() === $candidate->getName()) {
            return;
        }

        if ($collectedType->isInterface() && $candidate->implementsInterface($this->collectedType())) {
            return;
        }

        if ($collectedType->isAbstract() && $candidate->isSubclassOf($this->collectedType())) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf(
                "This collection can only contain items of type '%s', '%s' was given.",
                $this->collectedType(),
                $collectedType->getName()
            )
        );
    }
}
