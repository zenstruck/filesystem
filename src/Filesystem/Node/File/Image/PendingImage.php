<?php

namespace Zenstruck\Filesystem\Node\File\Image;

use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Image\LocalImage;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PendingImage extends PendingFile implements Image
{
    use DecoratedImage;

    /**
     * @template T of object
     *
     * @param object|callable(T):T $filter
     */
    public function transformInPlace(object|callable $filter, array $options = []): self
    {
        $this->localImage()->transformInPlace($filter, $options);

        return $this->refresh();
    }

    protected function localImage(): LocalImage
    {
        return $this->localImage ??= new LocalImage($this);
    }
}
