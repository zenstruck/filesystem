<?php

namespace Zenstruck\Filesystem\Node\File\Image;

use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\IsLazyNode;
use Zenstruck\Filesystem\Node\LazyNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class LazyImage implements Image, LazyNode
{
    use IsLazyNode, WrappedImage {
        IsLazyNode::path insteadof WrappedImage;
    }

    private Image $inner;

    protected function inner(): Image
    {
        return $this->inner ??= $this->filesystem()->image($this->path());
    }
}
