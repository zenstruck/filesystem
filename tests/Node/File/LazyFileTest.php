<?php

namespace Zenstruck\Filesystem\Tests\Node\File;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyFileTest extends TestCase
{
    use InteractsWithFilesystem;

    /**
     * @test
     */
    public function can_be_initialized_lazily(): void
    {
        $filesystem = $this->filesystem();
        $filesystem->write('foo/bar.txt', 'content');

        $node = new LazyFile('foo/bar.txt');
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
        $node = new LazyFile('foo/bar.txt');

        $this->assertSame('foo/bar.txt', $node->path());

        $this->expectException(\LogicException::class);

        $node->lastModified();
    }
}
