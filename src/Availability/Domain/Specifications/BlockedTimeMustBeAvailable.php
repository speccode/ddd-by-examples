<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain\Specifications;

use Speccode\Availability\Domain\Entities\Blockade;

final class BlockedTimeMustBeAvailable extends BlockingAvailableTimeSpecification
{
    public function isSatisfiedBy(Blockade $blockadeCandidate): bool
    {
        return $this->weeklyAvailability()
            ->openingHoursForDate($blockadeCandidate->startsAt())
            ->isTimeRangeAvailable($blockadeCandidate->startsAt(), $blockadeCandidate->timeRange());
    }
}
