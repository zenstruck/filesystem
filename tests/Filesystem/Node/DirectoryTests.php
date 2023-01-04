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

use Zenstruck\Filesystem\Node\Directory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait DirectoryTests
{
    /**
     * @test
     */
    public function metadata(): void
    {
        $dir = $this->createDirectory(fixture('sub1'), 'foo/bar');

        $this->assertTrue($dir->exists());
        $this->assertSame('foo/bar', $dir->path()->toString());
        $this->assertSame('bar', $dir->path()->name());
        $this->assertSame('bar', $dir->path()->basename());
        $this->assertNull($dir->path()->extension());
        $this->assertSame('foo', $dir->directory()->path()->toString());
        $this->assertSame('dir', $dir->mimeType());

        $dir = $this->createDirectory(fixture('sub1'), 'foo');

        $this->assertNull($dir->directory());
    }

    /**
     * @test
     */
    public function can_filter_and_iterate(): void
    {
        $dir = $this->createDirectory(fixture('sub1'), '');

        $this->assertCount(2, $dir);
        $this->assertCount(1, $dir->files());
        $this->assertCount(1, $dir->directories());
        $this->assertCount(5, $dir->recursive());
        $this->assertCount(3, $dir->recursive()->files());
        $this->assertCount(2, $dir->recursive()->directories());
    }

    /**
     * @test
     */
    public function older_than_filter(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function newer_than_filter(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function larger_than_filter(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function smaller_than_filter(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function matching_filter(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function not_matching_filter(): void
    {
        $this->markTestIncomplete();
    }

    abstract protected function createDirectory(\SplFileInfo $directory, string $path): Directory;
}
