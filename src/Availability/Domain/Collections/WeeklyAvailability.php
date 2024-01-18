<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain\Collections;

use Closure;
use DateTimeImmutable;
use LogicException;
use Speccode\Availability\Domain\Entities\OpeningHours;
use Speccode\Availability\Domain\ValueObjects\PublishDate;
use Speccode\Availability\Domain\ValueObjects\Week;
use Speccode\Kernel\Domain\ValueObjects\Collection;

/**
 * @method OpeningHours get(string $publishDate)
 */
final class WeeklyAvailability extends Collection
{
    protected function collectedType(): string
    {
        return OpeningHours::class;
    }

    protected function identifierCallback(): ?Closure
    {
        return function (OpeningHours $openingHours) {
            return $openingHours->publishDate()->toString();
        };
    }

    public function openingHoursForDate(DateTimeImmutable $searchedDate): OpeningHours
    {
        if ($this->isEmpty()) {
            return new OpeningHours(
                PublishDate::fromDateTime($searchedDate),
                Week::createEmpty()
            );
        }

        $found = null;
        $searchedDate = PublishDate::fromDateTime($searchedDate);

        foreach ($this as $openingHours) {
            $publishDate = $openingHours->publishDate();

            if ($publishDate->equals($searchedDate)) {
                return $openingHours;
            }

            if ($publishDate->isEarlierThan($searchedDate) && (! $found || $found->publishDate()->isEarlierThan($publishDate))) {
                $found = $openingHours;
            }
        }

        if (is_null($found)) {
            throw new LogicException('Could not find Opening Hours for given date');
        }

        return $found;
    }
}
