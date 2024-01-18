<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * @method static BlockadeType exception()
 * @method static BlockadeType booking()
 * @method static BlockadeType reservation()
 * @method static BlockadeType buffer()
 */
final class BlockadeType
{
    private string $type;
    private array $types = [
        'exception',
        'booking',
        'reservation',
        'buffer',
    ];

    private function __construct(string $type)
    {
        $this->guardValidType($type);

        $this->type = $type;
    }

    public static function fromString(string $type): self
    {
        return new static($type);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        return (string) $this->type;
    }

    public static function __callStatic(string $name, array $arguments): self
    {
        return static::fromString($name);
    }

    private function guardValidType(string $candidate)
    {
        if (! in_array($candidate, $this->types)) {
            throw new InvalidArgumentException('Wrong Block Type given.');
        }
    }

    public function equals(BlockadeType $other): bool
    {
        return $this->toString() === $other->toString();
    }

    public function notEquals(BlockadeType $other): bool
    {
        return ! $this->equals($other);
    }
}
