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
use Zenstruck\Image\Dimensions;
use Zenstruck\ImageFileInfo;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface Image extends File
{
    public const IMAGE_EXTENSIONS = ['gif', 'jpg', 'jpeg', 'png', 'svg', 'apng', 'avif', 'jfif', 'pjpeg', 'pjp', 'webp'];

    /**
     * @param string[]|string $filter
     */
    public function transformUrl(array|string $filter): string;

    /**
     * @param object|callable(object):object $filter
     * @param array<string,mixed>            $options
     */
    public function transform(callable|object $filter, array $options = []): PendingImage;

    public function dimensions(): Dimensions;

    /**
     * @return array<string,string>
     */
    public function exif(): array;

    /**
     * @return array<string,string>
     */
    public function iptc(): array;

    public function tempFile(): ImageFileInfo;
}
