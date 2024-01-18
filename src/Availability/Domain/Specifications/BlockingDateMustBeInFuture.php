<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain\Specifications;

use Speccode\Availability\Domain\Entities\Blockade;
use Speccode\Kernel\Domain\Contracts\DealsWithTime;

final class BlockingDateMustBeInFuture extends BlockingAvailableTimeSpecification
{
    use DealsWithTime;

    public function isSatisfiedBy(Blockade $blockadeCandidate): bool
    {
        return $blockadeCandidate->blockadeDateTimeRange()->isAfterWithTolerance(5, $this->now());
    }
}
