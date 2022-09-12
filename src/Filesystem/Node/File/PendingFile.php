<?php

namespace Zenstruck\Filesystem\Node\File;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\FilesystemRegistry;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Util;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PendingFile implements File
{
    use WrappedFile;

    private static FilesystemRegistry $filesystems;

    private string $path;
    private \SplFileInfo $file;
    private File $inner;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(\SplFileInfo|string $file, private array $config = [])
    {
        $this->file = \is_string($file) ? new \SplFileInfo($file) : $file;
        $this->path = $this->file->getFilename();
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return array<string,mixed>
     */
    public function config(): array
    {
        return $this->config;
    }

    public function localFile(): \SplFileInfo
    {
        return $this->file;
    }

    public function originalName(): string
    {
        return $this->file instanceof UploadedFile ? $this->file->getClientOriginalName() : $this->name();
    }

    public function originalNameWithoutExtension(): string
    {
        return Util::parseNameParts($this->originalName())[0];
    }

    public function originalExtension(): ?string
    {
        if ($this->file instanceof UploadedFile) {
            return Util::parseNameParts($this->file->getClientOriginalName())[1];
        }

        return $this->extension();
    }

    protected function inner(): File
    {
        if (isset($this->inner)) {
            return $this->inner;
        }

        if (!isset(self::$filesystems)) {
            self::$filesystems = new FilesystemRegistry();
        }

        $filesystem = self::$filesystems->get($dir = \dirname($this->file), fn() => new AdapterFilesystem($dir));

        return $this->inner = $filesystem->file($this->file->getFilename());
    }
}
