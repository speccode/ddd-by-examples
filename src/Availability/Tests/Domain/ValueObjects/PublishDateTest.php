<?php

namespace Speccode\Availability\Tests\Domain\ValueObjects;

use DateTime;
use InvalidArgumentException;
use Speccode\Availability\Domain\ValueObjects\PublishDate;
use PHPUnit\Framework\TestCase;

class PublishDateTest extends TestCase
{
    /** @test */
    public function publishDateSuccessfullyInstantiatedFromDateString()
    {
        $this->assertInstanceOf(
            PublishDate::class,
            PublishDate::fromDateTimeString('2020-03-15 12:21:11')
        );

        $this->assertInstanceOf(
            PublishDate::class,
            PublishDate::fromDateTimeString('2020-03-15')
        );
    }

    /** @test */
    public function publishDateSuccessfullyInstantiatedFromDateTimeObject()
    {
        $this->assertInstanceOf(
            PublishDate::class,
            PublishDate::fromDateTime(new DateTime())
        );
    }

    /** @test */
    public function throwExceptionWhenInvalidDateTimeStringProvided()
    {
        $this->expectException(InvalidArgumentException::class);

        PublishDate::fromDateTimeString('20.12.2020');
    }

    /** @test */
    public function equalsReturnsTrueWhenComparingWithSameDay()
    {
        $given = PublishDate::fromDateTimeString('2020-05-01');
        $same = PublishDate::fromDateTimeString('2020-05-01');
        $different = PublishDate::fromDateTimeString('2020-01-01');

        $this->assertTrue($given->equals($same));
        $this->assertFalse($given->equals($different));
    }

    /** @test */
    public function isEarlierThanReturnsTrueWhenComparingWithEarlierDate()
    {
        $given = PublishDate::fromDateTimeString('2020-05-01');
        $same = PublishDate::fromDateTimeString('2020-05-01');
        $later = PublishDate::fromDateTimeString('2020-04-01');
        $earlier = PublishDate::fromDateTimeString('2020-06-01');

        $this->assertFalse($given->isEarlierThan($same));
        $this->assertFalse($given->isEarlierThan($later));
        $this->assertTrue($given->isEarlierThan($earlier));
    }
}
