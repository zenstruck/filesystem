<?php

namespace Zenstruck\Filesystem\Node\File;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Filesystem\Flysystem\Operator;
use Zenstruck\Filesystem\FlysystemFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait IsPendingFile
{
    public function __construct(private \SplFileInfo $file)
    {
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

    public function originalExtensionWithDot(): ?string
    {
        if (!$ext = $this->originalExtension()) {
            return null;
        }

        return '.'.$ext;
    }

    protected function operator(): Operator
    {
        return $this->operator ??= self::$localOperators[$dir = \dirname($this->file)] ??= (new FlysystemFilesystem($dir))
            ->file($this->file->getFilename())
            ->operator()
        ;
    }
}
