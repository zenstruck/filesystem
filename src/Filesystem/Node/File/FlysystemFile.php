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

use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image\FlysystemImage;
use Zenstruck\Filesystem\Node\FlysystemNode;
use Zenstruck\Stream;
use Zenstruck\TempFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
class FlysystemFile extends FlysystemNode implements File
{
    private ?int $size = null;
    private ?string $mimeType = null;
    private array $checksum = [];

    public function guessExtension(): ?string
    {
        if (\is_string($ext = $this->path()->extension() ?? \array_search($this->mimeType(), GeneratedExtensionToMimeTypeMap::MIME_TYPES_FOR_EXTENSIONS, true))) {
            return $ext;
        }

        return null;
    }

    public function size(): int
    {
        return $this->size ??= $this->operator->fileSize($this->path());
    }

    public function contents(): string
    {
        return $this->operator->read($this->path());
    }

    public function read()
    {
        return $this->operator->readStream($this->path());
    }

    public function checksum(?string $algo = null): string
    {
        $config = $algo ? ['checksum_algo' => $algo] : [];

        return $this->checksum[$algo] ??= $this->operator->checksum($this->path(), $config);
    }

    public function publicUrl(array $config = []): string
    {
        return $this->operator->publicUrl($this->path(), $config);
    }

    public function temporaryUrl(\DateTimeInterface|string $expires, array $config = []): string
    {
        if (\is_string($expires)) {
            $expires = new \DateTimeImmutable($expires);
        }

        return $this->operator->temporaryUrl($this->path(), $expires, $config);
    }

    public function tempFile(): \SplFileInfo
    {
        $stream = Stream::wrap($this->read());

        try {
            return TempFile::for($stream->get());
        } finally {
            $stream->close();
        }
    }

    public function exists(): bool
    {
        return $this->operator->fileExists($this->path());
    }

    public function mimeType(): string
    {
        return $this->mimeType ??= $this->operator->mimeType($this->path());
    }

    public function refresh(): static
    {
        $this->size = $this->mimeType = null;
        $this->checksum = [];

        return parent::refresh();
    }

    public function ensureImage(): Image
    {
        if ($this instanceof FlysystemImage) {
            return $this;
        }

        $image = parent::ensureImage();
        $image->checksum = $this->checksum;
        $image->mimeType = $this->mimeType;
        $image->size = $this->size;

        return $image;
    }
}
