<?php

namespace Zenstruck\Filesystem\Node\File;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Filesystem\Adapter\Operator;
use Zenstruck\Filesystem\AdapterFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait IsPendingFile
{
    private \SplFileInfo $file;

    public function __construct(\SplFileInfo|string $file)
    {
        $this->file = \is_string($file) ? new \SplFileInfo($file) : $file;
        $this->path = (string) $file;
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
        return \pathinfo($this->originalName(), \PATHINFO_FILENAME);
    }

    public function originalExtension(): ?string
    {
        return $this->file instanceof UploadedFile ? $this->file->getClientOriginalExtension() : $this->extension();
    }

    protected function operator(): Operator
    {
        return $this->operator ??= self::$localOperators[$dir = \dirname($this->file)] ??= (new AdapterFilesystem($dir))
            ->file($this->file->getFilename())
            ->operator()
        ;
    }
}
