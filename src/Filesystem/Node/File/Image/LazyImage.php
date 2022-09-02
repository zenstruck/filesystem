<?php

namespace Zenstruck\Filesystem\Node\File\Image;

use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\IsLazyFile;
use Zenstruck\Filesystem\Node\LazyNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class LazyImage implements Image, LazyNode
{
    use IsLazyFile, WrappedImage {
        IsLazyFile::path insteadof WrappedImage;
    }

    private Image $inner;

    protected function inner(): Image
    {
        return $this->inner ??= $this->filesystem()->image($this->path());
    }
}
