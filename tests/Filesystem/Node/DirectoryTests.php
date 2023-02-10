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
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Test\Node\TestDirectory;

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
        $this->assertCount(2, $dir = $dir->recursive()->directories());
        $this->assertCount(2, $dir);
    }

    /**
     * @test
     */
    public function can_get_first(): void
    {
        $dir = $this->createDirectory(fixture(''), '');

        $this->assertSame('symfony.svg', $dir->files()->matchingFilename('*.svg')->first()?->path()->toString());
        $this->assertNull($dir->files()->matchingFilename('*.foo')->first());
    }

    /**
     * @test
     */
    public function date_range_filter(): void
    {
        $this->fixtureDir()
            ->recursive()
            ->files()
            ->date('> 30 years ago')
            ->assertCount(17)
            ->date('> tomorrow')
            ->assertCount(0)
        ;

        $this->fixtureDir()
            ->recursive()
            ->files()
            ->newerThan('30 years ago')
            ->assertCount(17)
            ->newerThan('tomorrow')
            ->assertCount(0)
        ;
    }

    /**
     * @test
     */
    public function size_range_filter(): void
    {
        $this->fixtureDir()
            ->recursive()
            ->files()
            ->size('< 1K')
            ->assertCount(9)
            ->size('> 0')
            ->assertCount(6)
        ;

        $this->fixtureDir()
            ->recursive()
            ->files()
            ->smallerThan('1K')
            ->assertCount(9)
            ->largerThan(0)
            ->assertCount(6)
        ;
    }

    /**
     * @test
     */
    public function matching_filename_filter(): void
    {
        $this->fixtureDir()
            ->recursive()
            ->matchingFilename(['symfony.*', 'file*'])
            ->assertCount(9)
            ->notMatchingFilename('*.png')
            ->assertCount(8)
        ;
    }

    /**
     * @test
     */
    public function matching_path_filter(): void
    {
        $this->fixtureDir()
            ->recursive()
            ->matchingPath('sub*/*')
            ->assertCount(4)
            ->notMatchingPath('sub1/*')
            ->assertCount(2)
        ;
    }

    /**
     * @test
     */
    public function custom_filter(): void
    {
        $this->fixtureDir()
            ->files()
            ->filter(fn(File $file) => $file->contents() === \file_get_contents(fixture('symfony.jpg')))
            ->assertCount(1)
            ->recursive()
            ->assertCount(2)
        ;
    }

    abstract protected function createDirectory(\SplFileInfo $directory, string $path): Directory;

    private function fixtureDir(): TestDirectory
    {
        return new TestDirectory($this->createDirectory(fixture(''), ''));
    }
}
