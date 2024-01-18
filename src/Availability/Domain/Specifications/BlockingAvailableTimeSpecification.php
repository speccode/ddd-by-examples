<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain\Specifications;

use Speccode\Availability\Domain\Collections\Blockades;
use Speccode\Availability\Domain\Collections\WeeklyAvailability;
use Speccode\Availability\Domain\Entities\Blockade;

abstract class BlockingAvailableTimeSpecification
{
    private WeeklyAvailability $weeklyAvailability;
    private Blockades $blockades;

    public function __construct(WeeklyAvailability $weeklyAvailability, Blockades $blockades)
    {
        $this->weeklyAvailability = $weeklyAvailability;
        $this->blockades = $blockades;
    }

    protected function weeklyAvailability(): WeeklyAvailability
    {
        return $this->weeklyAvailability;
    }

    protected function blockades(): Blockades
    {
        return $this->blockades;
    }

    abstract public function isSatisfiedBy(Blockade $blockadeCandidate): bool;

    public function isNotSatisfiedBy(Blockade $blockade): bool
    {
        return ! $this->isSatisfiedBy($blockade);
    }
}
