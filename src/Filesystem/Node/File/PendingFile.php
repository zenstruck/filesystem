<?php

namespace Zenstruck\Filesystem\Node\File;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Util;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PendingFile implements File
{
    use WrappedFile;

    /** @var array<string,AdapterFilesystem> */
    private static array $filesystems;

    private string $path;
    private \SplFileInfo $file;
    private File $inner;

    /** @var callable(self,object):string|array<string,mixed> */
    private mixed $config;

    /**
     * @param callable(self,object):string|array<string,mixed> $config
     */
    public function __construct(\SplFileInfo|string $file, callable|array $config = [])
    {
        $this->file = \is_string($file) ? new \SplFileInfo($file) : $file;
        $this->path = $this->file->getFilename();
        $this->config = $config;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @internal
     *
     * @return callable(self,object):string|array<string,mixed>
     */
    public function config(): mixed
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

        $filesystem = self::$filesystems[$dir = \dirname($this->file)] ??= new AdapterFilesystem($dir);

        return $this->inner = $filesystem->file($this->file->getFilename());
    }
}
