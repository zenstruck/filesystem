<?php

namespace Zenstruck\Filesystem\Tests\Node\File;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Filesystem\Node\LazyNode;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class LazyNodeTest extends TestCase
{
    use InteractsWithFilesystem;

    /**
     * @test
     */
    public function can_be_initialized_lazily(): void
    {
        $filesystem = $this->filesystem();
        $filesystem->write('foo/bar.txt', 'content');

        $node = $this->createNode('foo/bar.txt');
        $node->setFilesystem($filesystem);

        $this->assertSame('foo/bar.txt', $node->path());
        $this->assertSame('public', $node->visibility());
        $this->assertSame(7, $node->ensureFile()->size()->bytes());
    }

    /**
     * @test
     */
    public function must_have_filesystem_set_before_performing_any_filesystem_operations(): void
    {
        $node = $this->createNode('foo/bar.txt');

        $this->assertSame('foo/bar.txt', $node->path());

        $this->expectException(\LogicException::class);

        $node->lastModified();
    }

    protected function createNode(string $path): LazyNode|File
    {
        return new LazyFile($path);
    }
}
