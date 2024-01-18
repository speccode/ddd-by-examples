<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain\Specifications;

use Speccode\Availability\Domain\Entities\Blockade;

final class BlockedTimeMustNotAlreadyBeBlocked extends BlockingAvailableTimeSpecification
{
    public function isSatisfiedBy(Blockade $blockadeCandidate): bool
    {
        return $this->blockades()->hasNoCollidingBlockadesFor($blockadeCandidate);
    }
}
