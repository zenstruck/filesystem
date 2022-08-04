<?php

namespace Zenstruck\Filesystem\Tests;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\ResourceWrapper;
use Zenstruck\Filesystem\TempFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TempFileTest extends TestCase
{
    /**
     * @test
     */
    public function can_create_for_existing_file(): void
    {
        $file = new TempFile(\sys_get_temp_dir().'/zs'.__METHOD__);

        $this->assertFileDoesNotExist($file);

        \file_put_contents($file, 'contents');

        $this->assertStringEqualsFile($file, 'contents');
    }

    /**
     * @test
     */
    public function exists_when_created(): void
    {
        $this->assertFileExists(new TempFile());
    }

    /**
     * @test
     */
    public function can_delete(): void
    {
        \file_put_contents($file = new TempFile(), 'contents');

        $this->assertFileExists($file);

        $file->delete();
        $file->delete();

        $this->assertFileDoesNotExist($file);
    }

    /**
     * @test
     */
    public function cannot_create_for_directory(): void
    {
        $this->expectException(\LogicException::class);

        new TempFile(__DIR__);
    }

    /**
     * @test
     */
    public function can_create_for_stream(): void
    {
        $file = TempFile::for(ResourceWrapper::inMemory()->write('file contents')->rewind());

        $this->assertFileExists($file);
        $this->assertStringEqualsFile($file, 'file contents');
    }

    /**
     * @test
     */
    public function can_create_for_string(): void
    {
        $file = TempFile::for('file contents');

        $this->assertFileExists($file);
        $this->assertStringEqualsFile($file, 'file contents');
    }

    /**
     * @test
     */
    public function can_get_size(): void
    {
        $file = TempFile::for('foobar');

        $this->assertSame(6, $file->getSize());

        \file_put_contents($file, 'foobarbaz');

        $this->assertSame(9, $file->getSize());
    }

    /**
     * @test
     */
    public function can_purge_created_files(): void
    {
        $file1 = TempFile::for('contents');
        $file2 = TempFile::for('contents');

        $this->assertFileExists($file1);
        $this->assertFileExists($file2);

        TempFile::purge();

        $this->assertFileDoesNotExist($file1);
        $this->assertFileDoesNotExist($file2);
    }

    /**
     * @test
     */
    public function can_create_image(): void
    {
        $imageSize = \getimagesize(TempFile::image());

        $this->assertSame(10, $imageSize[0]);
        $this->assertSame(10, $imageSize[1]);
        $this->assertSame('image/jpeg', $imageSize['mime']);

        $imageSize = \getimagesize(TempFile::image(5, 6, 'PNG'));

        $this->assertSame(5, $imageSize[0]);
        $this->assertSame(6, $imageSize[1]);
        $this->assertSame('image/png', $imageSize['mime']);
    }
}
