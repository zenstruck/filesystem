<?php

namespace Zenstruck\Filesystem\Node\File;

use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;
use Zenstruck\Filesystem\Exception\NodeTypeMismatch;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image\FlysystemImage;
use Zenstruck\Filesystem\Node\FlysystemNode;
use Zenstruck\Stream;
use Zenstruck\TempFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class FlysystemFile extends FlysystemNode implements File
{
    private const IMAGE_EXTENSIONS = ['gif', 'jpg', 'jpeg', 'png', 'svg', 'apng', 'avif', 'jfif', 'pjpeg', 'pjp', 'webp'];

    private ?int $size = null;
    private ?string $mimeType = null;
    private array $checksum = [];

    public function extension(): ?string
    {
        return \mb_strtolower(\pathinfo($this->path(), \PATHINFO_EXTENSION)) ?: null;
    }

    public function guessExtension(): ?string
    {
        if (\is_string($ext = $this->extension() ?? \array_search($this->mimeType(), GeneratedExtensionToMimeTypeMap::MIME_TYPES_FOR_EXTENSIONS, true))) {
            return $ext;
        }

        return null;
    }

    public function nameWithoutExtension(): string
    {
        return \pathinfo($this->path(), \PATHINFO_FILENAME);
    }

    public function size(): int
    {
        return $this->size ??= $this->flysystem->fileSize($this->path());
    }

    public function contents(): string
    {
        return $this->flysystem->read($this->path());
    }

    public function read(): Stream
    {
        return Stream::wrap($this->flysystem->readStream($this->path()));
    }

    public function checksum(?string $algo = null): string
    {
        $config = $algo ? ['checksum_algo' => $algo] : [];

        return $this->checksum[$algo] ??= $this->flysystem->checksum($this->path(), $config);
    }

    public function publicUrl(array $config = []): string
    {
        return $this->flysystem->publicUrl($this->path(), $config);
    }

    public function temporaryUrl(\DateTimeInterface $expiresAt, array $config = []): string
    {
        return $this->flysystem->temporaryUrl($this->path(), $expiresAt, $config);
    }

    public function tempFile(): \SplFileInfo
    {
        $stream = $this->read();

        try {
            return TempFile::for($stream->get());
        } finally {
            $stream->close();
        }
    }

    public function exists(): bool
    {
        return $this->flysystem->fileExists($this->path());
    }

    public function mimeType(): string
    {
        return $this->mimeType ??= $this->flysystem->mimeType($this->path());
    }

    public function refresh(): static
    {
        $this->size = $this->mimeType = null;
        $this->checksum = [];

        return parent::refresh();
    }

    public function ensureImage(): Image
    {
        if ($this instanceof Image) {
            return $this;
        }

        if (!\in_array($this->guessExtension(), self::IMAGE_EXTENSIONS, true)) {
            throw new NodeTypeMismatch(\sprintf('Expected file at path "%s" to be an image but is "%s".', $this->path(), $this->mimeType()));
        }

        $image = new FlysystemImage($this->path(), $this->flysystem);
        $image->checksum = $this->checksum;
        $image->mimeType = $this->mimeType;
        $image->size = $this->size;
        $image->lastModified = $this->lastModified;
        $image->visibility = $this->visibility;

        return $image;
    }
}
