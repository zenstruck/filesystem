<?php

namespace Zenstruck\Filesystem\Tests\Node\File\Image;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Node\File\Image\LazyImage;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyImageTest extends TestCase
{
    use InteractsWithFilesystem;

    /**
     * @test
     */
    public function can_be_initialized_lazily(): void
    {
        $filesystem = $this->filesystem();
        $filesystem->write('foo/bar.png', 'content');

        $node = new LazyImage('foo/bar.png');
        $node->setFilesystem($filesystem);

        $this->assertSame('foo/bar.png', $node->path());
        $this->assertTrue($node->isImage());
        $this->assertSame('public', $node->visibility());
        $this->assertSame(7, $node->ensureFile()->size()->bytes());
    }
}
