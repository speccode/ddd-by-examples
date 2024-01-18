<?php

namespace Speccode\Kernel\Tests\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;
use Speccode\Kernel\Domain\ValueObjects\Time;
use PHPUnit\Framework\TestCase;

class TimeTest extends TestCase
{
    /** @test */
    public function timeSuccessfullyInstantiatedFromString(): void
    {
        $this->assertInstanceOf(
            Time::class,
            Time::fromString('8:00')
        );
    }

    /** @test */
    public function timeSuccessfullyInstantiatedFromStringWithoutRoundedMinutes(): void
    {
        $this->assertInstanceOf(
            Time::class,
            Time::fromString('8:11')
        );
    }

    /** @test */
    public function timeSuccessfullyInstantiatedFromIntegers(): void
    {
        $this->assertInstanceOf(
            Time::class,
            Time::fromIntegers(8, 0)
        );
    }

    /** @test */
    public function timeSuccessfullyInstantiatedFromFloat(): void
    {
        $this->assertInstanceOf(
            Time::class,
            Time::fromFloat(8.5)
        );
    }

    /** @test */
    public function timeSuccessfullyInstantiatedFromDateTime(): void
    {
        $this->assertInstanceOf(
            Time::class,
            Time::fromDateTime(new DateTimeImmutable())
        );
    }

    /** @test */
    public function timeSuccessfullyInstantiatedFromMinutes(): void
    {
        $this->assertInstanceOf(
            Time::class,
            Time::fromMinutes(30)
        );
    }

    /** @test */
    public function toStringReturnsProperFormat(): void
    {
        $timeFromIntegers = Time::fromIntegers(7, 30);
        $timeFromString = Time::fromString('12:30');

        $this->assertSame('07:30', $timeFromIntegers->toString());
        $this->assertSame('12:30', $timeFromString->toString());
    }

    /** @test */
    public function asIntegerReturnsZeroMinutesAsDoubleZero(): void
    {
        $time = Time::fromString('8:00');

        $this->assertSame(8.0, $time->asFloat());
    }

    /** @test */
    public function asIntegerReturnsProperFormat(): void
    {
        $time = Time::fromString('8:30');

        $this->assertSame(8.5, $time->asFloat());
    }

    /** @test */
    public function asIntegerReturnsProperQuarterTimeFloat(): void
    {
        $time = Time::fromString('8:15');

        $this->assertSame(8.25, $time->asFloat());
    }

    /** @test */
    public function throwExceptionWhenCreatedWithNegativeHour(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Time::fromIntegers(-1, 0);
    }

    /** @test */
    public function throwExceptionWhenCreatedWithNegativeMinutes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Time::fromIntegers(10, -30);
    }

    /** @test */
    public function isEarlierThanReturnsTrueIfComparedWithLaterTime(): void
    {
        $earlierTime = Time::fromString('8:00');
        $laterTime = Time::fromString('10:00');

        $this->assertTrue($earlierTime->isEarlierThan($laterTime));
    }

    /** @test */
    public function equalsWhenComparedToOtherTimeWithSameValue(): void
    {
        $timeA = Time::fromString('10:00');
        $timeB = Time::fromIntegers(10, 0);

        $this->assertTrue($timeA->equals($timeB));
    }

    /** @test */
    public function substractOneTimeFromAnother(): void
    {
        $timeA = Time::fromString('10:00');
        $timeB = Time::fromString('05:00');

        $result = $timeA->sub($timeB)->asInteger();

        $this->assertSame(500, $result);
    }

    /** @test */
    public function substractWillNotGoUnderZero(): void
    {
        $timeA = Time::fromString('01:00');
        $timeB = Time::fromString('10:00');

        $result = $timeA->sub($timeB)->asInteger();

        $this->assertSame(0, $result);
    }

    public static function provideTimeAddition(): array
    {
        return [
            [90, 5, 95],
            [11, 1, 12],
            [0, 5, 5],
            [0, 0, 0],
            [5, 0, 5],
        ];
    }

    /**
     * @test
     * @dataProvider provideTimeAddition
     * @param int $firstTimeMinutes
     * @param int $secondTimeMinutes
     * @param int $expectedMinutes
     */
    public function additionOneTimeToAnother(int $firstTimeMinutes, int $secondTimeMinutes, int $expectedMinutes): void
    {
        $timeA = Time::fromMinutes($firstTimeMinutes);
        $timeB = Time::fromMinutes($secondTimeMinutes);

        $result = $timeA->add($timeB)->asMinutes();

        $this->assertSame($expectedMinutes, $result);
    }

    public static function provideTimeFromMinutes(): array
    {
        return [
            [90, 1, 30],
            [12, 0, 12],
            [300, 5, 0],
            [301, 5, 1],
        ];
    }

    /**
     * @test
     * @dataProvider provideTimeFromMinutes
     * @param int $minutes
     * @param int $expectedHours
     * @param int $expectedMinutes
     */
    public function fromMinutesCreateProperTimeInstance(int $minutes, int $expectedHours, int $expectedMinutes): void
    {
        $time = Time::fromMinutes($minutes);

        $this->assertSame($expectedHours, $time->hourAsInteger());
        $this->assertSame($expectedMinutes, $time->minutesAsInteger());
    }

    /** @test */
    public function toMinutesReturnsProperFormat(): void
    {
        $time = Time::fromString('1:28');

        $this->assertSame(88, $time->asMinutes());
    }

    /** @test */
    public function laterThan(): void
    {
        $t1 = Time::fromString('08:00');
        $t2 = Time::fromString('08:01');
        $this->assertTrue($t2->isLaterThan($t1));
        $this->assertFalse($t1->isLaterThan($t2));
        $this->assertFalse($t1->isLaterThan($t1));
    }

    /** @test */
    public function earlierThan(): void
    {
        $t1 = Time::fromString('08:00');
        $t2 = Time::fromString('08:01');
        $this->assertTrue($t1->isEarlierThan($t2));
        $this->assertFalse($t2->isEarlierThan($t1));
        $this->assertFalse($t2->isEarlierThan($t2));
    }

    /** @test */
    public function earlierThanOrEqual(): void
    {
        $t1 = Time::fromString('08:00');
        $t2 = Time::fromString('08:01');
        $this->assertTrue($t1->isEarlierOrEqual($t2));
        $this->assertFalse($t2->isEarlierOrEqual($t1));
        $this->assertTrue($t2->isEarlierOrEqual($t2));
    }
}
