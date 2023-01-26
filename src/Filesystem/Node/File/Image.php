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
use Zenstruck\Image as LocalImage;
use Zenstruck\Image\Dimensions;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface Image extends File
{
    public function transformUrl(array|string $filter): string;

    /**
     * @template T of object
     *
     * @param object|callable(T):T $filter
     */
    public function transform(callable|object $filter, array $options = []): PendingImage;

    public function dimensions(): Dimensions;

    public function exif(): array;

    public function iptc(): array;

    public function tempFile(): LocalImage;
}
