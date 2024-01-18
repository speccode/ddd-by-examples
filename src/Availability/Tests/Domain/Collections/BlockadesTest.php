<?php

declare(strict_types=1);

namespace Speccode\Availability\Tests\Domain\Collections;

use Speccode\Availability\Domain\Collections\Blockades;
use Speccode\Availability\Domain\Entities\Blockade;
use Speccode\Availability\Domain\ValueObjects\BlockadeId;
use Speccode\Availability\Domain\ValueObjects\BlockadeType;
use Speccode\Kernel\Domain\ValueObjects\DateTimeRange;
use Speccode\Kernel\Domain\ValueObjects\Identities\BatchId;
use Speccode\Kernel\Domain\ValueObjects\TimeRange;
use PHPUnit\Framework\TestCase;

class BlockadesTest extends TestCase
{
    private function blockadeFactory(string $dateTimeRange, string $type = null): Blockade
    {
        $type = $type ? BlockadeType::fromString($type) : BlockadeType::booking();

        return new Blockade(
            BlockadeId::generate(),
            DateTimeRange::fromString($dateTimeRange),
            $type,
            BatchId::generate(),
        );
    }

    /** @test */
    public function blockadesSuccessfullyInstantiatedEmpty()
    {
        $this->assertInstanceOf(
            Blockades::class,
            Blockades::create([]),
        );
    }

    /** @test */
    public function blockadesSuccessfullyInstantiatedWithBlockades()
    {
        $this->assertInstanceOf(
            Blockades::class,
            Blockades::create([
                $this->blockadeFactory('2020-01-01 8:00-10:00'),
                $this->blockadeFactory('2020-01-01 10:00-12:00'),
                $this->blockadeFactory('2020-01-02 8:00-10:00'),
            ]),
        );
    }

    /** @test */
    public function hasNoCollidingBlockadesChecksCorrectlyForCollidingBlockades()
    {
        $blockades = Blockades::create([
            $this->blockadeFactory('2020-01-01 8:00-10:00'),
            $this->blockadeFactory('2020-01-01 10:00-12:00'),
            $this->blockadeFactory('2020-01-02 8:00-10:00'),
        ]);

        $collidingBlockade = $this->blockadeFactory('2020-01-01 9:30-10:00');
        $notCollidingBlockade = $this->blockadeFactory('2020-01-03 8:00-16:00');

        $this->assertFalse($blockades->hasNoCollidingBlockadesFor($collidingBlockade));
        $this->assertTrue($blockades->hasNoCollidingBlockadesFor($notCollidingBlockade));
    }

    /**
     * @test
     * @dataProvider providesSumOfBlockades
     */
    public function computesSumOfBlockades(array $blockades, int $expectedLength): void
    {
        //given
        $blockades = Blockades::create(
            array_map(fn (string $blockade) => $this->blockadeFactory($blockade), $blockades),
        );

        //when
        $result = $blockades->length();

        //then
        $this->assertSame($expectedLength, $result->asMinutes());
    }

    public static function providesSumOfBlockades(): array
    {
        return [
            [
                [
                    '2020-01-01 8:00-10:00',
                    '2020-01-01 10:00-12:01',
                ],
                241,
            ],
            [
                [
                    '2021-11-01 08:00-09:00',
                    '2021-11-01 09:05-10:00',
                    '2021-11-01 10:00-12:00',
                    '2021-11-01 13:00-16:00',
                ],
                415,
            ],
        ];
    }

    /** @test */
    public function computesSumOfBlockadesWithoutHavingInConsiderationOverlappingTimes(): void
    {
        //given
        $blockades = Blockades::create([
            $this->blockadeFactory('2020-01-01 7:00-10:00'),
            $this->blockadeFactory('2020-01-01 8:00-10:00'),
            $this->blockadeFactory('2020-01-01 10:00-12:01'),
        ]);

        //when
        $result = $blockades->length();

        //then
        $this->assertSame(301, $result->asMinutes());
    }

    /** @test */
    public function computingAvailableSlotsShouldAddSlotBetweenOpeningTimeAndFirstBlockade(): void
    {
        //given
        $openingHours = TimeRange::fromString('08:00-16:00');
        $expectedSlot = TimeRange::fromString('08:00-10:00');
        $blockades = Blockades::create([
            $this->blockadeFactory('2021-11-01 10:00-13:00'),
            $this->blockadeFactory('2021-11-01 13:00-16:00'),
        ]);

        //when
        $slots = $blockades->computeAvailableSlots($openingHours);

        //then
        $this->assertCount(1, $slots);
        $this->assertTrue($expectedSlot->equals($slots[0]));
    }

    /** @test */
    public function computingAvailableSlotsShouldAddSlotBetweenClosingTimeAndLastBlockade(): void
    {
        //given
        $openingHours = TimeRange::fromString('08:00-16:00');
        $expectedSlot = TimeRange::fromString('14:00-16:00');
        $blockades = Blockades::create([
            $this->blockadeFactory('2021-11-01 08:00-13:00'),
            $this->blockadeFactory('2021-11-01 13:00-14:00'),
        ]);

        //when
        $slots = $blockades->computeAvailableSlots($openingHours);

        //then
        $this->assertCount(1, $slots);
        $this->assertTrue($expectedSlot->equals($slots[0]));
    }

    /** @test */
    public function computingAvailableSlotsShouldAddSlotsBetweenBlockades(): void
    {
        //given
        $openingHours = TimeRange::fromString('08:00-16:00');
        $expectedSlots = [
            '09:00-09:05',
            '12:00-13:00',
        ];
        $blockades = Blockades::create([
            $this->blockadeFactory('2021-11-01 08:00-09:00'),
            $this->blockadeFactory('2021-11-01 09:05-10:00'),
            $this->blockadeFactory('2021-11-01 10:00-12:00'),
            $this->blockadeFactory('2021-11-01 13:00-16:00'),
        ]);

        //when
        $slots = $blockades->computeAvailableSlots($openingHours);
        $slotstoString = array_map(fn (TimeRange $timeRange) => $timeRange->toString(), $slots);

        //then
        $this->assertSame($expectedSlots, $slotstoString);
    }

    /** @test */
    public function computingAvailableSlotsShouldAddOneBigSlotWhenThereIsNoBlockades(): void
    {
        //given
        $openingHours = TimeRange::fromString('08:00-16:00');
        $expectedSlot = $openingHours;
        $blockades = Blockades::create();

        //when
        $slots = $blockades->computeAvailableSlots($openingHours);

        //then
        $this->assertCount(1, $slots);
        $this->assertTrue($expectedSlot->equals($slots[0]));
    }

    /** @test */
    public function computingAvailableSlotsShouldReturnNoSlotsWhenBlockadesTimeExedesGivenOpeningHours(): void
    {
        //given
        $openingHours = TimeRange::fromString('08:00-16:00');
        $blockades = Blockades::create([
            $this->blockadeFactory('2021-11-01 08:00-11:30'),
            $this->blockadeFactory('2021-11-01 11:00-16:00'),
        ]);

        //when
        $slots = $blockades->computeAvailableSlots($openingHours);

        //then
        $this->assertEmpty($slots);
    }

    /** @test */
    public function computingAvailableSlotsShouldConsiderOverlappingSlots(): void
    {
        // given
        $openingHours = TimeRange::fromString('08:00-16:00');
        $blockades = Blockades::create([
            $this->blockadeFactory('2021-11-01 08:00-11:30'),
            $this->blockadeFactory('2021-11-01 11:30-12:00'),
            $this->blockadeFactory('2021-11-01 13:00-14:00'),
            $this->blockadeFactory('2021-11-01 08:00-12:30', BlockadeType::buffer()->toString()),
        ]);
        $expectedSlots = [
            '12:30-13:00',
            '14:00-16:00',
        ];

        //when
        $slots = $blockades->computeAvailableSlots($openingHours);
        $slotstoString = array_map(fn (TimeRange $timeRange) => $timeRange->toString(), $slots);

        //then
        $this->assertEquals($expectedSlots, $slotstoString);
    }

    /** @test */
    public function hasNoBookingBlockadesChecksCorrectlyTroughAllBlockades(): void
    {
        //given
        $blockadesEmpty = Blockades::create([]);
        $blockadesWithBooking = Blockades::create([
            $this->blockadeFactory('2022-11-01 08:00-14:31', BlockadeType::buffer()->toString()),
            $this->blockadeFactory('2022-11-01 14:35-18:00', BlockadeType::booking()->toString()),
        ]);
        $blockadesWithBufferOnly = Blockades::create([
            $this->blockadeFactory('2022-11-01 08:00-14:31', BlockadeType::buffer()->toString()),
            $this->blockadeFactory('2022-11-01 14:35-18:00', BlockadeType::buffer()->toString()),
        ]);

        //then
        $this->assertTrue($blockadesEmpty->hasNoBookingBlockades());
        $this->assertFalse($blockadesWithBooking->hasNoBookingBlockades());
        $this->assertTrue($blockadesWithBufferOnly->hasNoBookingBlockades());
    }

    /** @test */
    public function hasASlotIfBlockadesHavePassed(): void
    {
        // given
        $openingHours = TimeRange::fromString('00:00-23:45');
        $blockades = Blockades::create([
            $this->blockadeFactory('2022-11-12 10:00-12:00', BlockadeType::booking()->toString()),
            $this->blockadeFactory('2022-11-12 12:00-15:40', BlockadeType::booking()->toString()),
            $this->blockadeFactory('2022-11-12 00:00-19:40', BlockadeType::buffer()->toString()),
        ]);

        // when
        $slots = $blockades->computeAvailableSlots($openingHours);

        // then
        $this->assertNotEmpty($slots);
    }
}
