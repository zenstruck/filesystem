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

use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToReadFile;
use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Zenstruck\Filesystem\Exception\NodeNotFound;
use Zenstruck\Filesystem\Exception\NodeTypeMismatch;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\Dsn;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image\PendingImage;
use Zenstruck\Filesystem\Node\Path;
use Zenstruck\Stream;
use Zenstruck\TempFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class PendingFile extends \SplFileInfo implements File
{
    private SymfonyFile|null $uploadedFile = null;
    private Path $path;
    private \SplFileInfo $tempFile;

    public function __construct(string|\SplFileInfo $filename)
    {
        if ($filename instanceof SymfonyFile) {
            $this->uploadedFile = $filename;
        }

        parent::__construct($filename);
    }

    public function path(): Path
    {
        if (isset($this->path)) {
            return $this->path;
        }

        if ($this->uploadedFile instanceof SymfonyUploadedFile) {
            return $this->path = new Path($this->uploadedFile->getClientOriginalName());
        }

        return $this->path = new Path($this);
    }

    /**
     * @internal
     */
    public function dsn(): Dsn
    {
        throw new \BadMethodCallException(\sprintf('%s is not supported for %s.', __METHOD__, static::class));
    }

    public function lastModified(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat('U', (string) $this->getMTime()) // @phpstan-ignore-line
            ->setTimezone(new \DateTimeZone(\date_default_timezone_get()))
        ;
    }

    public function exists(): bool
    {
        return \file_exists($this);
    }

    public function mimeType(): string
    {
        if ($this->uploadedFile instanceof SymfonyUploadedFile) {
            return $this->uploadedFile->getMimeType() ?? $this->uploadedFile->getClientMimeType();
        }

        if ($this->uploadedFile instanceof SymfonyFile && $mimeType = $this->uploadedFile->getMimeType()) {
            return $mimeType;
        }

        return $this->localFlysystem()->mimeType($this->getFilename());
    }

    public function refresh(): static
    {
        \clearstatcache(false, $this);

        return $this;
    }

    public function guessExtension(): ?string
    {
        if ($ext = $this->path()->extension()) {
            return $ext;
        }

        if ($this->uploadedFile instanceof SymfonyUploadedFile) {
            return $this->uploadedFile->guessClientExtension();
        }

        if ($this->uploadedFile instanceof SymfonyFile) {
            return $this->uploadedFile->guessExtension();
        }

        if (\is_string($ext = \array_search($this->mimeType(), GeneratedExtensionToMimeTypeMap::MIME_TYPES_FOR_EXTENSIONS, true))) {
            return $ext;
        }

        return null;
    }

    public function size(): int
    {
        return $this->getSize();
    }

    public function contents(): string
    {
        if (false === $contents = @\file_get_contents($this)) {
            throw UnableToReadFile::fromLocation($this);
        }

        return $contents;
    }

    public function read()
    {
        return $this->stream()->get();
    }

    public function stream(): Stream
    {
        return Stream::open($this, 'r');
    }

    public function checksum(?string $algo = null): string
    {
        return $this->localFlysystem()
            ->checksum($this->getFilename(), $algo ? ['checksum_algo' => $algo] : [])
        ;
    }

    public function tempFile(): \SplFileInfo
    {
        return $this->tempFile ??= TempFile::for($this);
    }

    /**
     * @internal
     */
    public function publicUrl(array $config = []): string
    {
        throw new \BadMethodCallException(\sprintf('%s is not supported for %s.', __METHOD__, static::class));
    }

    /**
     * @internal
     */
    public function temporaryUrl(\DateTimeInterface|string $expires, array $config = []): string
    {
        throw new \BadMethodCallException(\sprintf('%s is not supported for %s.', __METHOD__, static::class));
    }

    /**
     * @internal
     */
    public function directory(): ?Directory
    {
        throw new \BadMethodCallException(\sprintf('%s is not supported for %s.', __METHOD__, static::class));
    }

    public function visibility(): string
    {
        return $this->localFlysystem()->visibility($this->getFilename());
    }

    public function ensureFile(): self
    {
        return $this;
    }

    public function ensureExists(): static
    {
        if (!$this->exists()) {
            throw new NodeNotFound($this->path(), '(pending-file)');
        }

        return $this;
    }

    public function ensureDirectory(): Directory
    {
        throw NodeTypeMismatch::expectedDirectoryAt($this->path());
    }

    public function ensureImage(): PendingImage
    {
        if ($this instanceof PendingImage) {
            return $this;
        }

        $image = new PendingImage($this);
        $image->uploadedFile = $this->uploadedFile;

        return $image;
    }

    private function localFlysystem(): Flysystem
    {
        return new Flysystem(new LocalFilesystemAdapter(\dirname($this)));
    }
}
