<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Node;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Node\Mapping;
use Zenstruck\Filesystem\Node\Metadata;
use Zenstruck\Filesystem\Node\Path\Expression;
use Zenstruck\Filesystem\Node\Path\Namer;
use Zenstruck\Filesystem\Twig\Template;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MappingTest extends TestCase
{
    /**
     * @test
     * @dataProvider validNamerProvider
     */
    public function valid_namer($value, $expected): void
    {
        $mapping = new Mapping(Metadata::DSN, namer: $value);

        $this->assertEquals($expected, $mapping->namer());
    }

    public static function validNamerProvider(): iterable
    {
        yield [__CLASS__, new Namer(__CLASS__)];
        yield ['@alias', new Namer('alias')];
        yield ['expression:foo/bar', new Expression('foo/bar')];
        yield [new Expression('foo/bar'), new Expression('foo/bar')];
        yield ['twig:foo/bar', new Template('foo/bar')];
        yield [new Template('foo/bar'), new Template('foo/bar')];
    }

    /**
     * @test
     * @dataProvider invalidNamerProvider
     */
    public function invalid_namer($value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Unable to parse namer "%s".', $value));

        new Mapping(Metadata::DSN, namer: $value);
    }

    public static function invalidNamerProvider(): iterable
    {
        yield ['foo/bar'];
        yield ['foo:bar'];
    }
}
