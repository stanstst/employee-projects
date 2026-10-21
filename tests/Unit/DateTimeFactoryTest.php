<?php

namespace Tests\Unit;

use App\Utils\DateTimeFactory;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DateTimeFactoryTest extends TestCase
{
    public static function supportedDates(): array
    {
        return [
            'Y-m-d' => ['2013-11-01'],
            'Y/m/d' => ['2013/11/01'],
            'Y.m.d' => ['2013.11.01'],
            'Ymd' => ['20131101'],
            'd.m.Y' => ['01.11.2013'],
        ];
    }

    public static function unsupportedDates(): array
    {
        return [
            'm/d/Y' => ['12/31/2013'],
            'd/m/Y' => ['01/11/2013'],
            'with time' => ['2013-11-01T10:00:00'],
            'text' => ['abc'],
            'impossible Y-m-d' => ['2013-02-30'],
            'impossible d.m.Y' => ['31.02.2013'],
            'empty' => [''],
        ];
    }

    #[DataProvider('supportedDates')]
    public function test_each_supported_format_is_detected(string $date): void
    {
        $this->assertSame('2013-11-01 00:00:00', (new DateTimeFactory)->create($date)->format('Y-m-d H:i:s'));
    }

    #[DataProvider('unsupportedDates')]
    public function test_other_values_are_rejected(string $date): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DateTimeFactory)->create($date);
    }

    public function test_the_example_is_in_the_first_format(): void
    {
        $this->assertSame('2013-11-01', (new DateTimeFactory)->example());
    }
}
