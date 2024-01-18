<?php

declare(strict_types=1);

namespace Speccode\Availability\Tests\Domain\Specification;

use PHPUnit\Framework\TestCase;
use Speccode\Availability\Domain\Specifications\MinimumBookingTime;
use Speccode\Kernel\Domain\ValueObjects\TimeRange;

class MinimumBookingTimeTest extends TestCase
{
    /**
     * @test
     * @dataProvider providesTimeRanges
     */
    public function checksIfGivenTimeRangeMeetsExpectations(string $timeRange, bool $expected): void
    {
        //given
        $timeRange = TimeRange::fromString($timeRange);

        //when
        $result = MinimumBookingTime::isSatisfiedBy($timeRange);

        //then
        $this->assertSame($expected, $result);
    }

    public static function providesTimeRanges(): array
    {
        return [
            'one minute' => ['00:00-00:01', false],
            '29 minutes' => ['00:00-00:29', false],
            'exactly 30 minutes' => ['00:00-00:30', true],
            '31 minutes' => ['00:00-00:31', true],
            'few hours' => ['00:00-11:00', true],
        ];
    }
}
