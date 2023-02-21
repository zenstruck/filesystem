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
use Symfony\Component\DependencyInjection\ServiceLocator;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\Path\Expression;
use Zenstruck\Filesystem\Node\Path\Namer;
use Zenstruck\Filesystem\Node\PathGenerator;
use Zenstruck\Tests\Fixtures\CustomPathGenerator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PathGeneratorTest extends TestCase
{
    /**
     * @test
     * @dataProvider generators
     */
    public function can_generate_paths(PathGenerator $generator): void
    {
        $file = in_memory_filesystem()->write('some/file.txt', 'content')->ensureFile();

        $this->assertSame('from/callback', $generator->generate(fn(File $f, array $context) => 'from/callback', $file));
        $this->assertSame('from/custom.txt', $generator->generate('custom', $file));
        $this->assertSame('from/custom.txtfoo:bar', $generator->generate('custom', $file, ['foo' => 'bar']));
        $this->assertSame('from/custom.txtbaz:foofoo:bar', $generator->generate(new Namer('custom', ['baz' => 'foo']), $file, ['foo' => 'bar']));
        $this->assertSame('9a0364b.txt', $generator->generate(Expression::checksum(7), $file));
    }

    /**
     * @test
     * @dataProvider generators
     */
    public function namer_not_found(PathGenerator $generator): void
    {
        $file = in_memory_filesystem()->write('some/file.txt', 'content')->ensureFile();

        $this->expectException(\InvalidArgumentException::class);

        $generator->generate('invalid', $file);
    }

    public static function generators(): iterable
    {
        yield [new PathGenerator(['custom' => new CustomPathGenerator()])];
        yield [new PathGenerator(new ServiceLocator(['custom' => fn() => new CustomPathGenerator()]))];
    }
}
