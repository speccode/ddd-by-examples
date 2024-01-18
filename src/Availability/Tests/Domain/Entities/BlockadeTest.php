<?php

declare(strict_types=1);

namespace Speccode\Availability\Tests\Domain\Entities;

use DateTimeImmutable;
use Speccode\Availability\Domain\Entities\Blockade;
use Speccode\Availability\Domain\ValueObjects\BlockadeId;
use Speccode\Availability\Domain\ValueObjects\BlockadeType;
use Speccode\Kernel\Domain\ValueObjects\DateTimeRange;
use Speccode\Kernel\Domain\ValueObjects\Identities\BatchId;
use Speccode\Kernel\Domain\ValueObjects\TimeRange;
use PHPUnit\Framework\TestCase;

class BlockadeTest extends TestCase
{
    private function tomorrowsBlockade(TimeRange $timeRange = null): Blockade
    {
        $timeRange = $timeRange ?? TimeRange::fromString('8:00-10:00')->toString();
        $date = (new DateTimeImmutable('tomorrow'))->format('Y-m-d');

        return new Blockade(
            BlockadeId::generate(),
            DateTimeRange::fromString($date . ' ' . $timeRange),
            BlockadeType::exception(),
            BatchId::generate()
        );
    }

    /** @test */
    public function blockadeSuccessfullyInstantiated()
    {
        $this->assertInstanceOf(
            Blockade::class,
            $this->tomorrowsBlockade()
        );
    }

    /** @test */
    public function startsAtReturnsDateTimeBasedOnBlockadeDateAndTimeRange()
    {
        $blockade = $this->tomorrowsBlockade(TimeRange::fromString('10:00-12:00'));
        $expectedDate = (new DateTimeImmutable('tomorrow'))->setTime(10, 0);

        $this->assertSame(
            $expectedDate->format('Y-m-d H:i:s'),
            $blockade->startsAt()->format('Y-m-d H:i:s')
        );
    }

    /** @test */
    public function endsAtReturnsDateTimeBasedOnBlockadeDateAndTimeRange()
    {
        $blockade = $this->tomorrowsBlockade(TimeRange::fromString('10:00-12:00'));
        $expectedDate = (new DateTimeImmutable('tomorrow'))->setTime(12, 0);

        $this->assertSame(
            $expectedDate->format('Y-m-d H:i:s'),
            $blockade->endsAt()->format('Y-m-d H:i:s')
        );
    }

    /** @test */
    public function overlapsReturnsTrueWhenBlockadeOverlappingFullyGiven()
    {
        $blockade = $this->tomorrowsBlockade(TimeRange::fromString('10:00-12:00'));
        $blockadeOverlappingFully = $this->tomorrowsBlockade(TimeRange::fromString('10:00-12:00'));

        $this->assertTrue($blockadeOverlappingFully->overlaps($blockade));
    }

    /** @test */
    public function overlapsReturnsTrueWhenBlockadeOverlappingPartlyGiven()
    {
        $blockade = $this->tomorrowsBlockade(TimeRange::fromString('10:00-12:00'));
        $blockadeOverlappingPartlyLater = $this->tomorrowsBlockade(TimeRange::fromString('11:00-14:00'));
        $blockadeOverlappingPartlyEarlier = $this->tomorrowsBlockade(TimeRange::fromString('9:00-11:00'));

        $this->assertTrue($blockadeOverlappingPartlyLater->overlaps($blockade));
        $this->assertTrue($blockadeOverlappingPartlyEarlier->overlaps($blockade));
    }

    /** @test */
    public function overlapsReturnsFalseWhenBlockadeFromOtherDayGiven()
    {
        $blockade = $this->tomorrowsBlockade(TimeRange::fromString('10:00-12:00'));
        $dayAfterTomorrow = (new DateTimeImmutable('+2 days'))->format('Y-m-d');
        $blockadeOtherDay = new Blockade(
            BlockadeId::generate(),
            DateTimeRange::fromString($dayAfterTomorrow . ' 10:00-12:00'),
            BlockadeType::exception(),
            BatchId::generate()
        );

        $this->assertFalse($blockadeOtherDay->overlaps($blockade));
    }

    /** @test */
    public function overlapsReturnsFalseWhenBlockadeWithNotOverlappingTimeGiven()
    {
        $blockade = $this->tomorrowsBlockade(TimeRange::fromString('10:00-12:00'));
        $blockadeSameDayNoOverlapping = $this->tomorrowsBlockade(TimeRange::fromString('16:00-17:00'));

        $this->assertFalse($blockadeSameDayNoOverlapping->overlaps($blockade));
    }
}
