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
use Zenstruck\Image\Dimensions;
use Zenstruck\ImageFileInfo;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class FlysystemImage extends FlysystemFile implements Image
{
    public function transform(callable|object $filter, array $options = []): PendingImage
    {
        return new PendingImage($this->tempFile()->transform($filter, $options));
    }

    public function transformUrl(array|string $filter, array $config = []): string
    {
        return $this->operator->transformUrl($this->path(), $filter, $config);
    }

    public function dimensions(): Dimensions
    {
        return $this->tempFile()->dimensions();
    }

    public function exif(): array
    {
        return $this->tempFile()->exif();
    }

    public function iptc(): array
    {
        return $this->tempFile()->iptc();
    }

    public function tempFile(): ImageFileInfo
    {
        return parent::tempFile(); // @phpstan-ignore-line
    }

    protected function createTempFile(): \SplFileInfo
    {
        return new ImageFileInfo(parent::createTempFile());
    }
}
