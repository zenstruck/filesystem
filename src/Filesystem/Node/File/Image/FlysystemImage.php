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

use Zenstruck\Filesystem\Node\File\FlysystemFile;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FlysystemImage extends FlysystemFile implements Image
{
    use DecoratedImage;

    public function transform(callable|object $filter, array $options = []): PendingImage
    {
        return new PendingImage(
            $this->operator->imageTransformer()->transform($this->localImage(), $filter, $options),
            $this->operator->imageTransformer()
        );
    }

    public function transformUrl(array|string $filter, array $config = []): string
    {
        return $this->operator->transformUrl($this->path(), $filter, $config);
    }
}
