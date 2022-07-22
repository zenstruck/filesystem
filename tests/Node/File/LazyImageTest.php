<?php

namespace Zenstruck\Filesystem\Tests\Node\File;

use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\LazyImage;
use Zenstruck\Filesystem\Node\LazyNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyImageTest extends LazyNodeTest
{
    /**
     * @test
     */
    public function always_is_image(): void
    {
        $this->assertTrue($this->createNode('foo/bar.txt')->isImage());
    }

    protected function createNode(string $path): LazyNode|File
    {
        return new LazyImage($path);
    }
}
