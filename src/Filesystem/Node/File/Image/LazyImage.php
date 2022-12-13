<?php

namespace Zenstruck\Filesystem\Node\File\Image;

use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\LazyFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyImage extends LazyFile implements Image
{
    use DecoratedImage;

    protected function inner(): Image
    {
        return $this->inner ??= $this->filesystem()->image($this->path()); // @phpstan-ignore-line
    }
}
