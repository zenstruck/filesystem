<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Exception\NodeNotFound;
use Zenstruck\Filesystem\Exception\NodeTypeMismatch;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Stream;
use Zenstruck\TempFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class FilesystemTest extends TestCase
{
    /**
     * @test
     */
    public function can_get_node(): void
    {
        $fs = $this->createFilesystem();
        $fs->write('some/file.txt', 'content');

        $file = $fs->node('some/file.txt')->ensureExists();
        $dir = $fs->node('some')->ensureExists();

        $this->assertSame('content', $file->ensureFile()->contents());
        $this->assertCount(1, $dir->ensureDirectory());
    }

    /**
     * @test
     */
    public function node_not_found(): void
    {
        $fs = $this->createFilesystem();

        $this->expectException(NodeNotFound::class);

        $fs->node('invalid');
    }

    /**
     * @test
     */
    public function can_get_file(): void
    {
        $fs = $this->createFilesystem();
        $fs->write('some/file.txt', 'content');

        $this->assertTrue($fs->file('some/file.txt')->ensureExists()->exists());
    }

    /**
     * @test
     */
    public function invalid_file(): void
    {
        $fs = $this->createFilesystem();
        $fs->mkdir('dir');

        $this->expectException(NodeTypeMismatch::class);

        $fs->file('dir');
    }

    /**
     * @test
     */
    public function can_get_image(): void
    {
        $fs = $this->createFilesystem();
        $fs->write('some/file.png', 'content');

        $this->assertTrue($fs->image('some/file.png')->exists());
    }

    /**
     * @test
     */
    public function invalid_image(): void
    {
        $fs = $this->createFilesystem();
        $fs->mkdir('dir');

        $this->expectException(NodeTypeMismatch::class);

        $fs->image('dir');
    }

    /**
     * @test
     */
    public function invalid_image_file(): void
    {
        $fs = $this->createFilesystem();
        $fs->write('some/file.txt', 'content');

        $this->expectException(NodeTypeMismatch::class);

        $fs->image('some/file.txt');
    }

    /**
     * @test
     */
    public function can_get_directory(): void
    {
        $fs = $this->createFilesystem();
        $fs->write('some/file1.txt', 'content');

        $this->assertTrue($fs->directory('some')->exists());
    }

    /**
     * @test
     */
    public function can_get_root_directory(): void
    {
        $fs = $this->createFilesystem();
        $fs->write('file1.txt', 'content');
        $fs->write('file2.txt', 'content');
        $fs->write('nested/file3.txt', 'content');

        $this->assertCount(3, $fs->directory());
        $this->assertCount(2, $fs->directory()->files());
        $this->assertCount(1, $fs->directory()->directories());
        $this->assertCount(4, $fs->directory()->recursive());
        $this->assertCount(3, $fs->directory()->files()->recursive());
        $this->assertCount(1, $fs->directory()->directories()->recursive());
    }

    /**
     * @test
     */
    public function invalid_directory(): void
    {
        $fs = $this->createFilesystem();
        $fs->write('some/file.txt', 'content');

        $this->expectException(NodeTypeMismatch::class);

        $fs->directory('some/file.txt');
    }

    /**
     * @test
     */
    public function can_check_for_existence(): void
    {
        $fs = $this->createFilesystem();
        $fs->write('some/file.txt', 'content');

        $this->assertTrue($fs->has('some/file.txt'));
        $this->assertTrue($fs->has('some'));
        $this->assertFalse($fs->has('invalid'));
    }

    /**
     * @test
     */
    public function can_copy_file(): void
    {
        $fs = $this->createFilesystem();
        $fs->write('some/file.txt', 'content');

        $this->assertTrue($fs->has('some/file.txt'));
        $this->assertFalse($fs->has('another/file.txt'));

        $fs->copy('some/file.txt', 'another/file.txt');

        $this->assertTrue($fs->has('some/file.txt'));
        $this->assertTrue($fs->has('another/file.txt'));
    }

    /**
     * @test
     */
    public function can_move_file(): void
    {
        $fs = $this->createFilesystem();
        $fs->write('some/file.txt', 'content');

        $this->assertTrue($fs->has('some/file.txt'));
        $this->assertFalse($fs->has('another/file.txt'));

        $fs->move('some/file.txt', 'another/file.txt');

        $this->assertFalse($fs->has('some/file.txt'));
        $this->assertTrue($fs->has('another/file.txt'));
    }

    /**
     * @test
     */
    public function can_delete_file(): void
    {
        $fs = $this->createFilesystem();
        $fs->write('some/file.txt', 'content');

        $this->assertTrue($fs->has('some/file.txt'));

        $fs->delete('some/file.txt');

        $this->assertFalse($fs->has('some/file.txt'));
    }

    /**
     * @test
     */
    public function can_delete_directory(): void
    {
        $fs = $this->createFilesystem();
        $fs->write('some/file.txt', 'content');
        $fs->write('some/sub/file.txt', 'content');

        $this->assertTrue($fs->has('some/file.txt'));
        $this->assertTrue($fs->has('some/sub/file.txt'));

        $fs->delete('some/sub');

        $this->assertTrue($fs->has('some/file.txt'));
        $this->assertFalse($fs->has('some/sub'));
    }

    /**
     * @test
     */
    public function can_make_directory(): void
    {
        $fs = $this->createFilesystem();

        $this->assertFalse($fs->has('dir'));

        $fs->mkdir('dir');

        $this->assertTrue($fs->has('dir'));
    }

    /**
     * @test
     */
    public function can_chmod(): void
    {
        $fs = $this->createFilesystem();
        $fs->write('some/file.txt', 'content');

        $this->assertSame('public', $fs->file('some/file.txt')->visibility());

        $fs->chmod('some/file.txt', 'private');

        $this->assertSame('private', $fs->file('some/file.txt')->visibility());
    }

    /**
     * @test
     * @dataProvider writeValueProvider
     */
    public function can_write_file(mixed $value): void
    {
        $fs = $this->createFilesystem();

        $file = $fs->write('some/file.txt', $value);

        $this->assertSame('content', $file->contents());
        $this->assertSame('some/file.txt', $file->path()->toString());
    }

    public static function writeValueProvider(): iterable
    {
        yield ['content'];
        yield [TempFile::for('content')];
        yield [Stream::inMemory()->write('content')->rewind()->get()];
        yield [in_memory_filesystem()->write('file.txt', 'content')];
    }

    /**
     * @test
     * @dataProvider mkdirContentsProvider
     */
    public function can_mkdir_with_contents(mixed $value): void
    {
        $fs = $this->createFilesystem();

        $fs->mkdir('foo', $value);

        $this->assertTrue($fs->has('foo'));
        $this->assertTrue($fs->has('foo/file1.txt'));
        $this->assertTrue($fs->has('foo/sub2'));
        $this->assertTrue($fs->has('foo/sub2/file2.txt'));
    }

    /**
     * @test
     * @dataProvider mkdirContentsProvider
     */
    public function can_mkdir_with_contents_and_progress(mixed $value): void
    {
        $fs = $this->createFilesystem();
        $nodes = [];

        $fs->mkdir('foo', $value, ['progress' => function(File $file) use (&$nodes) {
            $nodes[] = $file->path()->toString();
        }]);

        \sort($nodes);

        $this->assertTrue($fs->has('foo'));
        $this->assertTrue($fs->has('foo/file1.txt'));
        $this->assertTrue($fs->has('foo/sub2'));
        $this->assertTrue($fs->has('foo/sub2/file2.txt'));
        $this->assertSame([
            'foo/file1.txt',
            'foo/sub2/file2.txt',
            'foo/sub2/sub3/file2.txt',
        ], $nodes);
    }

    public static function mkdirContentsProvider(): iterable
    {
        yield [fixture_filesystem()->directory('sub1')->recursive()];
        yield [fixture('sub1')];
    }

    /**
     * @test
     * @dataProvider invalidWriteValueProvider
     */
    public function invalid_write_value(mixed $value): void
    {
        $fs = $this->createFilesystem();

        $this->expectException(\InvalidArgumentException::class);
        $fs->write('some/file.txt', $value);
    }

    public static function invalidWriteValueProvider(): iterable
    {
        yield [['array']];
    }

    abstract protected function createFilesystem(): Filesystem;
}
