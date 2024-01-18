<?php

declare(strict_types=1);

namespace Speccode\Kernel\Domain\Specifications;

final class OrSpecification
{
    private array $specifications;

    public function __construct(...$specifications)
    {
        $this->specifications = $specifications;
    }

    public function isSatisfiedBy(...$candidate): bool
    {
        foreach ($this->specifications as $specification) {
            if ($specification->isSatisfiedBy(...$candidate)) {
                return true;
            }
        }

        return false;
    }

    public function isNotSatisfiedBy(...$candidate): bool
    {
        return ! $this->isSatisfiedBy(...$candidate);
    }
}
