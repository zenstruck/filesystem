<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    public function transformUrl(array|string $filter): string
    {
        throw new \BadMethodCallException(\sprintf('%s is not supported for %s.', __METHOD__, static::class));
    }

    protected function localImage(): LocalImage
    {
        return $this->localImage ??= new LocalImage($this);
    }
}
