<?php

declare(strict_types=1);

namespace Speccode\Kernel\Domain\ValueObjects\Traits;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid as UuidLib;

trait Uuid
{
    private string $uuid;

    final public function __construct(string $uuid)
    {
        $this->guardValidUuid($uuid);
        $this->uuid = $uuid;
    }

    /**
     * @return static
     */
    public static function generate(): self
    {
        return new static((string) UuidLib::uuid4());
    }

    /**
     * @param string $string
     * @return static
     */
    public static function fromString(string $string): self
    {
        return new static($string);
    }

    public function toString(): string
    {
        return (string) $this->uuid;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function equals(self $other): bool
    {
        return $this->toString() === $other->toString();
    }

    public function notEquals(self $other): bool
    {
        return ! $this->equals($other);
    }

    private function guardValidUuid(string $uuid): void
    {
        if (! UuidLib::isValid($uuid)) {
            throw new InvalidArgumentException('Invalid UUID.');
        }
    }
}
