<?php

namespace Zenstruck\Filesystem\Tests\Node;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Node\FileSize;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FileSizeTest extends TestCase
{
    /**
     * @test
     * @dataProvider fromBinaryProvider
     */
    public function can_create_from_binary(int $bytes, string $humanized): void
    {
        $size = FileSize::binary($bytes);

        $this->assertSame($bytes, $size->bytes());
        $this->assertSame($humanized, (string) $size);
    }

    public static function fromBinaryProvider(): iterable
    {
        yield [0, '0 B'];
        yield [400, '400 B'];
        yield [1023, '1,023 B'];
        yield [1024, '1 KiB'];
        yield [54651654, '52.12 MiB'];
    }

    /**
     * @test
     * @dataProvider fromDecimalProvider
     */
    public function can_create_from_decimal(int $bytes, string $humanized): void
    {
        $size = FileSize::decimal($bytes);

        $this->assertSame($bytes, $size->bytes());
        $this->assertSame($humanized, (string) $size);
    }

    public static function fromDecimalProvider(): iterable
    {
        yield [0, '0 B'];
        yield [400, '400 B'];
        yield [999, '999 B'];
        yield [1000, '1 kB'];
        yield [54651654, '54.65 MB'];
    }

    /**
     * @test
     * @dataProvider fromValueProvider
     */
    public function can_create_from_value(string|int $value, $bytes, string $humanized): void
    {
        $size = FileSize::from($value);

        $this->assertSame((int) $bytes, $size->bytes());
        $this->assertSame($humanized, (string) $size);
    }

    public static function fromValueProvider(): iterable
    {
        yield [400, 400, '400 B'];
        yield ['400', 400, '400 B'];
        yield ['400B', 400, '400 B'];
        yield ['400  B', 400, '400 B'];
        yield ['400  b', 400, '400 B'];
        yield ['4.2 KB', 4200, '4.2 kB'];
        yield ['4.2 MB', 4.2e+6, '4.2 MB'];
        yield ['4.2 mb', 4.2e+6, '4.2 MB'];
        yield ['521565415613.5468 kb', 521565415613546, '521.57 TB'];
        yield ['4.2 KiB', 4300, '4.2 KiB'];
        yield ['4.2 MiB', 4404019, '4.2 MiB'];
        yield ['4.2 mib', 4404019, '4.2 MiB'];
        yield ['521565415613.5468 kib', 534082985588271, '485.75 TiB'];
        yield ['400K', 400000, '400 kB'];
    }

    /**
     * @test
     */
    public function can_convert_between_formats(): void
    {
        $size = FileSize::decimal(1024);

        $this->assertSame('1.02 kB', (string) $size);

        $size = $size->asBinary();

        $this->assertSame('1 KiB', (string) $size);

        $size = $size->asDecimal();

        $this->assertSame('1.02 kB', (string) $size);
    }

    /**
     * @test
     */
    public function from_string_invalid_string(): void
    {
        $this->expectException(\LogicException::class);

        FileSize::from('foobar');
    }

    /**
     * @test
     */
    public function from_string_invalid_unit(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FileSize::from('6.6 foo');
    }

    /**
     * @test
     * @dataProvider comparisonProvider
     */
    public function comparison_test(string|int $first, string $method, string|int $second, bool $expected): void
    {
        $this->assertSame($expected, FileSize::from($first)->{$method}($second));
    }

    public static function comparisonProvider(): iterable
    {
        yield [10, 'isLargerThan', 5, true];
        yield [10, 'isLargerThan', 15, false];
        yield [10, 'isLargerThan', 10, false];
        yield ['1.2 TB', 'isLargerThan', '1.1TB', true];
        yield ['1.2 TB', 'isLargerThan', '1.3TB', false];

        yield [10, 'isLargerThanOrEqualTo', 5, true];
        yield [10, 'isLargerThanOrEqualTo', 15, false];
        yield [10, 'isLargerThanOrEqualTo', 10, true];
        yield ['1.2 TB', 'isLargerThanOrEqualTo', '1.1TB', true];
        yield ['1.2 TB', 'isLargerThanOrEqualTo', '1.3TB', false];

        yield [10, 'isSmallerThan', 5, false];
        yield [10, 'isSmallerThan', 15, true];
        yield [10, 'isSmallerThan', 10, false];
        yield ['1.2 TB', 'isSmallerThan', '1.3TB', true];
        yield ['1.2 TB', 'isSmallerThan', '1.1TB', false];

        yield [10, 'isSmallerThanOrEqualTo', 5, false];
        yield [10, 'isSmallerThanOrEqualTo', 15, true];
        yield [10, 'isSmallerThanOrEqualTo', 10, true];
        yield ['1.2 TB', 'isSmallerThanOrEqualTo', '1.3TB', true];
        yield ['1.2 TB', 'isSmallerThanOrEqualTo', '1.1TB', false];

        yield [10, 'isEqualTo', 5, false];
        yield [10, 'isEqualTo', 15, false];
        yield [10, 'isEqualTo', 10, true];
        yield ['1.2 TB', 'isEqualTo', '1.3TB', false];
        yield ['1.2 TB', 'isEqualTo', '1.1TB', false];
        yield ['1.2 TB', 'isEqualTo', '1.2 TB', true];
    }

    /**
     * @test
     * @dataProvider withinRangeProvider
     */
    public function within_range(string|int $value, string|int $min, string|int $max, bool $expected): void
    {
        $this->assertSame($expected, FileSize::from($value)->isWithin($min, $max));
    }

    public static function withinRangeProvider(): iterable
    {
        yield [56, 40, 60, true];
        yield [40, 40, 40, true];
        yield [39, 40, 60, false];
        yield [80, 40, 60, false];
        yield ['1.3GB', '1.1GB', '1.5GiB', true];
        yield ['1.3MB', '1.1GB', '1.5GiB', false];
        yield ['1.3TB', '1.1GB', '1.5GiB', false];
    }
}
