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
use Zenstruck\Image\Dimensions;
use Zenstruck\Image\Hash\ThumbHash;
use Zenstruck\ImageFileInfo;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
final class PendingImage extends PendingFile implements Image
{
    private ImageFileInfo $localImage;

    public function __construct(\SplFileInfo|string $filename)
    {
        parent::__construct($filename);
    }

    /**
     * @param object|callable(object):object $filter
     * @param array<string,mixed>            $options
     */
    public function transformInPlace(object|callable $filter, array $options = []): self
    {
        $this->transform($filter, \array_merge($options, ['output' => $this]));

        return $this->refresh();
    }

    public function transform(callable|object $filter, array $options = []): self
    {
        return new self($this->localImage()->transform($filter, $options));
    }

    public function dimensions(): Dimensions
    {
        return $this->localImage()->dimensions();
    }

    public function thumbHash(): ThumbHash
    {
        return $this->localImage()->thumbHash();
    }

    public function exif(): array
    {
        return $this->localImage()->exif();
    }

    public function iptc(): array
    {
        return $this->localImage()->iptc();
    }

    public function transformUrl(array|string $filter): Uri
    {
        throw new \BadMethodCallException(\sprintf('%s is not supported for %s.', __METHOD__, static::class));
    }

    public function refresh(): static
    {
        unset($this->localImage);

        return parent::refresh();
    }

    public function tempFile(): ImageFileInfo
    {
        return new ImageFileInfo(parent::tempFile());
    }

    private function localImage(): ImageFileInfo
    {
        return $this->localImage ??= new ImageFileInfo($this);
    }
}
