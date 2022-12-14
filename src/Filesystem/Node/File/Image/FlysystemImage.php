<?php

namespace Zenstruck\Filesystem\Node\File\Image;

use Zenstruck\Filesystem\Node\File\FlysystemFile;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FlysystemImage extends FlysystemFile implements Image
{
    use DecoratedImage;

    public function transformUrl(array|string $filter): string
    {
        return $this->publicUrl(['filter' => $filter]);
    }
}
