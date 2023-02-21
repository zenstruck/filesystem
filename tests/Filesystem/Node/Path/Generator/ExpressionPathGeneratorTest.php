<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Node\Path\Generator;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\Path\Expression;
use Zenstruck\Filesystem\Node\Path\Generator\ExpressionPathGenerator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ExpressionPathGeneratorTest extends TestCase
{
    /**
     * @test
     */
    public function generate_with_default_expression(): void
    {
        $this->assertMatchesRegularExpression(
            '#^foo-bar-[0-9a-z]{6}$#',
            $this->name($this->file('some/FoO BaR', 'content'))
        );
        $this->assertMatchesRegularExpression(
            '#^foo-bar-[0-9a-z]{6}\.txt$#',
            $this->name($this->file('some/FoO BaR.TxT', 'content'))
        );
    }

    /**
     * @test
     */
    public function can_use_expression_object(): void
    {
        $context = ['expression' => Expression::slugify()];

        $this->assertSame('foo-bar', $this->name($this->file('some/FoO BaR'), $context));
        $this->assertSame('foo-bar.txt', $this->name($this->file('some/FoO BaR.txt'), $context));
        $this->assertSame('foo-bar.txt', $this->name($this->file('some/FoO BaR.tXt'), $context));
        $this->assertSame(
            '9a0364b9e99bb480dd25e1f0284c8555',
            $this->name($this->file('foo/bar', 'content'), ['expression' => Expression::checksum()])
        );
        $this->assertSame(
            '9a0364b9e99bb480dd25e1f0284c8555.txt',
            $this->name($this->file('foo/bar.txt', 'content'), ['expression' => Expression::checksum()])
        );
        $this->assertSame(
            '9a0364b9e99bb480dd25e1f0284c8555.txt',
            $this->name($this->file('foo/bar.TxT', 'content'), ['expression' => Expression::checksum()])
        );
        $this->assertSame(
            '040f06fd774092478d450774f5ba30c5da78acc8.txt',
            $this->name($this->file('foo/bar.TxT', 'content'), ['expression' => Expression::checksum(algorithm: 'sha1')]
            )
        );
        $this->assertSame(
            '040f06f.txt',
            $this->name($this->file('foo/bar.TxT', 'content'), ['expression' => Expression::checksum(7, 'sha1')])
        );
    }

    /**
     * @test
     */
    public function custom_expression(): void
    {
        $file = $this->file('some/pATh.txt', 'content');

        $this->assertSame(
            'a/prefix/path--9a0364b9e99bb480dd25e1f0284c8555.txt',
            $this->name($file, ['expression' => 'a/prefix/{name}--{checksum}{ext}'])
        );
        $this->assertSame(
            'a/prefix/path--9a0364b.txt',
            $this->name($file, ['expression' => 'a/prefix/{name}--{checksum:7}{ext}'])
        );
        $this->assertSame(
            'a/prefix/path--040f06fd774092478d450774f5ba30c5da78acc8.txt',
            $this->name($file, ['expression' => 'a/prefix/{name}--{checksum:sha1}{ext}'])
        );
        $this->assertSame(
            'a/prefix/path--040f06f.txt',
            $this->name($file, ['expression' => 'a/prefix/{name}--{checksum:7:sha1}{ext}'])
        );
        $this->assertSame(
            'a/prefix/path--040f06f.txt',
            $this->name($file, ['expression' => 'a/prefix/{name}--{checksum:sha1:7}{ext}'])
        );
    }

    /**
     * @test
     */
    public function expression_with_rand(): void
    {
        $file = $this->file('some/pATh.txt', 'content');

        $name1 = $this->name($file, ['expression' => '{rand}-{rand}']);
        $name2 = $this->name($file, ['expression' => '{rand}-{rand}']);

        $this->assertMatchesRegularExpression('#^[0-9a-z]{6}-[0-9a-z]{6}$#', $name1);
        $this->assertMatchesRegularExpression('#^[0-9a-z]{6}-[0-9a-z]{6}$#', $name2);
        $this->assertNotSame($name1, $name2);
        $this->assertSame(13, \mb_strlen($name1));
    }

    /**
     * @test
     */
    public function can_customize_rand_length(): void
    {
        $file = $this->file('some/pATh.txt', 'content');

        $name1 = $this->name($file, ['expression' => '{rand:3}-{rand:10}']);
        $name2 = $this->name($file, ['expression' => '{rand:3}-{rand:10}']);

        $this->assertMatchesRegularExpression('#^[0-9a-z]{3}-[0-9a-z]{10}$#', $name1);
        $this->assertMatchesRegularExpression('#^[0-9a-z]{3}-[0-9a-z]{10}$#', $name2);
        $this->assertNotSame($name1, $name2);
        $this->assertSame(14, \mb_strlen($name1));
    }

    /**
     * @test
     */
    public function can_use_context_as_expression_variables(): void
    {
        $file = $this->file('some/pATh.txt', 'content');

        $this->assertSame(
            'prefix/baz/value/stRIng/1//prop1-valUe/6/2022-02-03/0-1-3',
            $this->name($file, [
                'expression' => 'prefix/{foo.bar}/{array.key}/{object}/{object.prop1}/{object.prop2}/{object.prop3}/{object.prop4}{object.prop5()}/{object.prop6.format(Y-m-d)}/{object.implode(0, 1, 3)}',
                'foo.bar' => 'baz',
                'array' => ['key' => 'value'],
                'object' => new ContextObject(),
            ])
        );
    }

    /**
     * @test
     */
    public function can_access_raw_file_values(): void
    {
        $file = $this->file('some/pATh.tXt', 'content');

        $this->assertSame(
            'prefix/9a0364b9e99bb480dd25e1f0284c8555-pATh.tXt',
            $this->name($file, [
                'expression' => 'prefix/{file.checksum}-{file.path.name}',
            ])
        );
    }

    /**
     * @test
     */
    public function can_use_variable_modifiers(): void
    {
        $file = $this->file('some/pA Th.tXt', 'content');

        $this->assertSame(
            'prefix/string/prop1-value/pa-th--9A0364B.txt',
            $this->name($file, [
                'expression' => 'prefix/{object|slug}/{object.prop3|lower}/{file.path.basename|slug}--{checksum:7|upper}{ext}',
                'object' => new ContextObject(),
            ])
        );
    }

    /**
     * @test
     */
    public function invalid_expression_variable(): void
    {
        $file = $this->file('some/pATh.txt', 'content');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to access "invalid.foo".');

        $this->name($file, ['expression' => 'prefix/{invalid.foo}']);
    }

    private function name(File $file, array $context = []): string
    {
        return (new ExpressionPathGenerator())->generatePath($file, $context);
    }

    private function file(string $path, string $content = ''): File
    {
        return in_memory_filesystem()->write($path, $content);
    }
}

class ContextObject
{
    public $prop1 = true;
    public $prop2 = false;
    private $prop3 = 'prop1-valUe';
    private $prop4 = 6;
    private $prop5;
    private $prop6;

    public function __construct()
    {
        $this->prop6 = new \DateTimeImmutable('Feb 3, 2022');
    }

    public function __toString(): string
    {
        return 'stRIng';
    }

    public function getProp3(): string
    {
        return $this->prop3;
    }

    public function getProp4(): int
    {
        return $this->prop4;
    }

    public function getProp5()
    {
        return $this->prop5;
    }

    public function getProp6()
    {
        return $this->prop6;
    }

    public function implode(string ...$values)
    {
        return \implode('-', $values);
    }
}
