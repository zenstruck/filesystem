<?php

namespace Zenstruck\Tests\Filesystem\Node;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Node\Directory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class DirectoryTest extends TestCase
{
    /**
     * @test
     */
    public function metadata(): void
    {
        $dir = $this->createDirectory(fixture('sub1'), 'foo/bar');

        $this->assertTrue($dir->exists());
        $this->assertSame('foo/bar', $dir->path());
        $this->assertSame('bar', $dir->name());
        $this->assertSame('foo', $dir->directory()->path());
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

    abstract protected function createDirectory(\SplFileInfo $directory, string $path): Directory;
}
