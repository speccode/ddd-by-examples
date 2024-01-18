<?php

declare(strict_types=1);

namespace Speccode\Availability\Tests\Domain\Entities;

use DateTimeImmutable;
use Speccode\Availability\Domain\Collections\Blockades;
use Speccode\Availability\Domain\Entities\Availability;
use Speccode\Availability\Domain\Entities\Blockade;
use Speccode\Availability\Domain\ValueObjects\BlockadeId;
use Speccode\Availability\Domain\ValueObjects\BlockadeType;
use Speccode\Availability\Domain\ValueObjects\ResourceId;
use Speccode\Availability\Domain\ValueObjects\Week;
use Speccode\Availability\Domain\ValueObjects\Weekday;
use Speccode\Kernel\Domain\ValueObjects\DateTimeRange;
use Speccode\Kernel\Domain\ValueObjects\Identities\BatchId;
use Speccode\Kernel\Domain\ValueObjects\TimeRange;
use Speccode\Kernel\Domain\Contracts\DealsWithTime;
use PHPUnit\Framework\TestCase;
use Speccode\Kernel\Tests\Traits\TestsTime;

class AvailabilityTest extends TestCase
{
    use TestsTime;
    use DealsWithTime;

    private function availability(
        DateTimeImmutable $date,
        ?TimeRange $openClosesForWeek,
        ?Blockades $blockades,
    ): Availability
    {
        if (is_null($blockades)) {
            $blockades = Blockades::create();
        }

        $openingHours = Week::create([
            Weekday::monday($openClosesForWeek),
            Weekday::tuesday($openClosesForWeek),
            Weekday::wednesday($openClosesForWeek),
            Weekday::thursday($openClosesForWeek),
            Weekday::friday($openClosesForWeek),
            Weekday::saturday(),
            Weekday::sunday(),
        ]);

        return new Availability(
            ResourceId::generate(),
            $date,
            $openingHours,
            $blockades,
        );
    }

    /** @test */
    public function availabilitySuccessfullyInstantiated(): void
    {
        $this->assertInstanceOf(
            Availability::class,
            $this->availability(
                $this->now(),
                TimeRange::fromString(('08:00-16:00')),
                null,
            ),
        );
    }

    /** @test */
    public function addsBlockadeFromOpeningTimeToNowWhenBlockadesAreSetForTodaysAvailability(): void
    {
        //given
        $this->setTestNow(new DateTimeImmutable('2021-11-01 12:00:00'));
        $expectedBlockade = TimeRange::fromString('08:00-12:00');

        //when
        $availability = $this->availability(
            $this->now(),
            TimeRange::fromString('08:00-16:00'),
            Blockades::create(),
        );

        //then
        $firstBlockade = $availability->blockades()->first()->timeRange();
        $this->assertTrue($expectedBlockade->equals($firstBlockade));
    }

    /** @test */
    public function doesNotAddBlockadeAtBeginningWhenOpeningTimeEqualsNow(): void
    {
        //given
        $this->setTestNow(new DateTimeImmutable('2021-11-01 08:00:20'));

        //when
        $availability = $this->availability(
            $this->now(),
            TimeRange::fromString('08:00-16:00'),
            Blockades::create(),
        );

        //then
        $this->assertTrue($availability->blockades()->isEmpty());
    }

    /**
     * @test
     * @dataProvider providesAvailabilityWithBlockades
     */
    public function hasAvailabilityOnDataSet(
        string $dateTime,
        string $date,
        ?string $openingTimeRange,
        array $blockadesTimeRanges,
        bool $expected,
    ): void
    {
        //given
        $this->setTestNow(new DateTimeImmutable($dateTime));
        $date = new DateTimeImmutable($date);
        if ($openingTimeRange) {
            $openClosesForWeek = TimeRange::fromString($openingTimeRange);
        } else {
            $openClosesForWeek = null;
        }
        $blockades = Blockades::create();
        foreach ($blockadesTimeRanges as $blockade) {
            $blockadeDateTimeRange = DateTimeRange::fromString(sprintf(
                '%s %s',
                $date->format('Y-m-d'),
                $blockade,
            ));
            $blockades = $blockades->add(
                new Blockade(
                    BlockadeId::generate(),
                    $blockadeDateTimeRange,
                    BlockadeType::booking(),
                    BatchId::generate(),
                )
            );
        }
        $availability = $this->availability(
            $date,
            $openClosesForWeek,
            $blockades,
        );

        //when
        $result = $availability->hasAvailableTime();


        //then
        $this->assertSame($expected, $result);
    }

    public static function providesAvailabilityWithBlockades(): array
    {
        return [
            'today when current time is during other blockade and rest of the day is also blocked' => [
                '2021-11-01 11:30:00',
                '2021-11-01',
                '08:00-16:00',
                ['08:00-12:00', '12:00-16:00'],
                false,
            ],
            'today when current time is during other blockade but rest of the day is available' => [
                '2021-11-01 11:30:00',
                '2021-11-01',
                '08:00-16:00',
                ['08:00-12:00'],
                true,
            ],
            'today when current time is during other blockade but there is nothing else blocked before and after is fully blocked' => [
                '2021-11-01 11:30:00',
                '2021-11-01',
                '08:00-16:00',
                ['11:00-16:00'],
                false,
            ],
            'today before opening time' => [
                '2021-11-01 06:00:00',
                '2021-11-01',
                '08:00-16:00',
                [],
                true,
            ],
            'today after opening time but before closing time' => [
                '2021-11-01 15:00:00',
                '2021-11-01',
                '08:00-16:00',
                [],
                true,
            ],
            'today after closing time' => [
                '2021-11-01 16:10:00',
                '2021-11-01',
                '08:00-16:00',
                [],
                false,
            ],
            'tomorrow, open, no blockades' => [
                '2021-11-01 15:00:00',
                '2021-11-02',
                '08:00-16:00',
                [],
                true,
            ],
            'tomorrow, no opening time, no blockades' => [
                '2021-11-01 15:00:00',
                '2021-11-02',
                null,
                [],
                false,
            ],
            'tomorrow, fully blocked, one blockade' => [
                '2021-11-01 15:00:00',
                '2021-11-02',
                '08:00-16:00',
                ['08:00-16:00'],
                false,
            ],
            'tomorrow, fully blocked, multiple blockades' => [
                '2021-11-01 15:00:00',
                '2021-11-02',
                '08:00-16:00',
                ['08:00-10:00', '10:00-13:00', '13:00-16:00'],
                false,
            ],
            'tomorrow, no minimum time available in one slot' => [
                '2021-11-01 15:00:00',
                '2021-11-02',
                '08:00-16:00',
                ['08:00-10:00', '10:10-13:00', '13:20-16:00'],
                false,
            ],
            'tomorrow, one minimum slot time available' => [
                '2021-11-01 15:00:00',
                '2021-11-02',
                '08:00-16:00',
                ['08:00-10:00', '10:30-16:00'],
                true,
            ],
        ];
    }
}
