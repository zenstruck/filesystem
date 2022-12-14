<?php

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
