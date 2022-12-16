<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node\File;

use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image\PendingImage;
use Zenstruck\Image as BaseImage;
use Zenstruck\Image\LocalImage;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface Image extends File, BaseImage
{
    public function transformUrl(array|string $filter): string;

    public function transform(callable|object $filter, array $options = []): PendingImage;

    public function tempFile(): LocalImage;
}
