<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Node\File;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Node\Dsn;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Filesystem\Node\Mapping;
use Zenstruck\Filesystem\Node\Path\Expression;
use Zenstruck\Filesystem\Node\PathGenerator;
use Zenstruck\Tests\Filesystem\Node\FileTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class LazyFileTest extends TestCase
{
    use FileTests;

    /**
     * @test
     */
    public function filesystem_must_be_set_for_non_lazy_methods(): void
    {
        $file = $this->createLazyFile('some/path.txt');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Filesystem not set.');

        $file->contents();
    }

    /**
     * @test
     */
    public function path_must_be_set_before_accessing(): void
    {
        $file = $this->createLazyFile();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Path not set.');

        $file->path();
    }

    /**
     * @test
     */
    public function path_related_methods_are_lazy(): void
    {
        $file = $this->createLazyFile('some/path.txt');

        $this->assertSame('txt', $file->path()->extension());
        $this->assertSame('txt', $file->guessExtension());
        $this->assertSame('path.txt', $file->path()->name());
        $this->assertSame('path', $file->path()->basename());
    }

    /**
     * @test
     */
    public function can_set_path_and_filesystem(): void
    {
        $filesystem = in_memory_filesystem();
        $filesystem->write('some/image.png', 'content');
        $file = $this->createLazyFile();

        $file->setFilesystem($filesystem);
        $file->setPath(fn() => 'some/image.png');

        $this->assertSame('content', $file->contents());
    }

    /**
     * @test
     */
    public function can_use_callable_for_path(): void
    {
        $count = 0;
        $file = $this->createLazyFile(function() use (&$count) {
            ++$count;

            return 'some/image.png';
        });

        $this->assertSame('some/image.png', $file->path()->toString());
        $this->assertSame('some/image.png', $file->path()->toString());
        $this->assertSame(1, $count);
    }

    /**
     * @test
     */
    public function can_use_callable_for_filesystem(): void
    {
        $filesystem = in_memory_filesystem();
        $filesystem->write('some/image.png', 'content');
        $count = 0;
        $file = $this->createLazyFile('some/image.png');
        $file->setFilesystem(function() use (&$count, $filesystem) {
            ++$count;

            return $filesystem;
        });

        $this->assertSame('content', $file->contents());
        $this->assertSame('content', $file->contents());
        $this->assertSame(1, $count);
    }

    /**
     * @test
     */
    public function can_check_for_existence_without_loading_file(): void
    {
        $file = $this->createLazyFile('some/file.png');
        $file->setFilesystem($filesystem = in_memory_filesystem());

        $this->assertFalse($file->exists());

        $filesystem->write('some/file.png', 'content');

        $this->assertTrue($file->exists());
    }

    /**
     * @test
     */
    public function can_create_with_path_as_attribute(): void
    {
        $this->assertSame('foo', $this->createLazyFile(['path' => 'foo'])->path()->toString());
        $this->assertSame('foo', $this->createLazyFile(['path' => fn() => 'foo'])->path()->toString());
    }

    /**
     * @test
     */
    public function can_create_with_attributes(): void
    {
        $fs = in_memory_filesystem();
        $fs->write('some/file.png', 'content');
        $file = $this->createLazyFile([
            'path' => 'some/file.png',
            'dsn' => 'some://path',
            'last_modified' => '2023-01-01',
            'visibility' => 'private',
            'mime_type' => 'image/png',
            'size' => 72,
            'checksum' => 'foo',
            'public_url' => 'http://example.com',
        ]);
        $file->setFilesystem($fs);

        $this->assertSame('some://path', $file->dsn()->toString());
        $this->assertEquals(new \DateTimeImmutable('2023-01-01'), $file->lastModified());
        $this->assertSame('private', $file->visibility());
        $this->assertSame('image/png', $file->mimeType());
        $this->assertSame(72, $file->size());
        $this->assertSame('foo', $file->checksum());
        $this->assertSame('http://example.com', $file->publicUrl());

        $this->assertSame('040f06fd774092478d450774f5ba30c5da78acc8', $file->checksum('sha1'));

        $file->refresh();

        $this->assertSame('default://some/file.png', $file->dsn()->toString());
        $this->assertNotEquals(new \DateTimeImmutable('2023-01-01'), $file->lastModified());
        $this->assertSame('public', $file->visibility());
        $this->assertSame('image/png', $file->mimeType());
        $this->assertSame(7, $file->size());
        $this->assertSame('9a0364b9e99bb480dd25e1f0284c8555', $file->checksum());
        $this->assertSame('/prefix/some/file.png', $file->publicUrl());
    }

    /**
     * @test
     */
    public function multiple_checksums(): void
    {
        $fs = in_memory_filesystem();
        $fs->write('some/file.png', 'content');
        $file = $this->createLazyFile([
            'path' => 'some/file.png',
            'checksum' => [
                'md5' => 'foo',
                'sha1' => 'bar',
            ],
        ]);
        $file->setFilesystem($fs);

        $this->assertSame('foo', $file->checksum('md5'));
        $this->assertSame('bar', $file->checksum('sha1'));
        $this->assertSame('9a0364b9e99bb480dd25e1f0284c8555', $file->checksum());
    }

    /**
     * @test
     * @dataProvider dsnProvider
     */
    public function can_create_from_dsn(array|string|Dsn|callable $dsn): void
    {
        $file = $this->createLazyFile($dsn);

        $this->assertSame('some/file.png', $file->path()->toString());
        $this->assertSame('public://some/file.png', $file->dsn()->toString());
    }

    public static function dsnProvider(): iterable
    {
        yield ['public://some/file.png'];
        yield [Dsn::wrap('public://some/file.png')];
        yield [['dsn' => 'public://some/file.png']];
    }

    /**
     * @test
     */
    public function can_generate_path(): void
    {
        $file = $this->createLazyFile([
            Mapping::CHECKSUM => 'foobar',
            Mapping::EXTENSION => 'jpg',
        ]);

        $file->setPath(fn() => (new PathGenerator())->generate(new Expression('files/{checksum}{ext}'), $file));

        $this->assertSame('files/foobar.jpg', $file->path()->toString());
    }

    /**
     * @test
     */
    public function generated_path_cannot_contain_placeholder(): void
    {
        $file = $this->createLazyFile([
            Mapping::CHECKSUM => 'foobar',
            Mapping::EXTENSION => 'jpg',
        ]);

        $file->setPath(fn() => (new PathGenerator())->generate(new Expression('files/{name}{ext}'), $file));

        $this->expectException(\LogicException::class);

        $file->path();
    }

    protected function createLazyFile(string|callable|array|null $attributes = null): LazyFile
    {
        return new LazyFile($attributes);
    }

    protected function createFile(\SplFileInfo $file, string $path): File
    {
        $this->filesystem->write($path, $file);

        $lazyFile = new LazyFile($path);
        $lazyFile->setFilesystem($this->filesystem);

        return $lazyFile;
    }
}
