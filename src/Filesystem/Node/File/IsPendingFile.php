<?php

namespace Zenstruck\Filesystem\Node\File;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Filesystem\Adapter\Operator;
use Zenstruck\Filesystem\AdapterFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait IsPendingFile
{
    private \SplFileInfo $file;

    public function __construct(\SplFileInfo|string $file, ?string $path = null)
    {
        $this->file = \is_string($file) ? new \SplFileInfo($file) : $file;
        $this->path = $path ?: $this->file->getFilename();
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
        return self::parseNameParts($this->originalName())[0];
    }

    public function originalExtension(): ?string
    {
        if ($this->file instanceof UploadedFile) {
            return self::parseNameParts($this->file->getClientOriginalName())[1];
        }

        return $this->extension();
    }

    protected function operator(): Operator
    {
        return $this->operator ??= self::$localOperators[$dir = \dirname($this->file)] ??= (new AdapterFilesystem($dir))
            ->file($this->file->getFilename())
            ->operator()
        ;
    }
}
