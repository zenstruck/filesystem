<?php

namespace Zenstruck\Filesystem\Node\File\Image;

use Closure;
use SplFileInfo;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\FilesystemRegistry;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Util;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class PendingImage implements Image
{
    use WrappedImage;

    private static FilesystemRegistry $filesystems;

    private \SplFileInfo $file;
    private Image $inner;

    /**
     * @param Closure(): SplFileInfo $generator
     * @param array<string,mixed>    $config
     */
    public function __construct(
        private Image $originalFile,
        private Closure $generator,
        private array $config = []
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function config(): array
    {
        return $this->config;
    }

    public function localFile(): SplFileInfo
    {
        return $this->file();
    }

    public function originalName(): string
    {
        return $this->originalFile instanceof UploadedFile ? $this->originalFile->getClientOriginalName() : $this->originalFile->name();
    }

    public function originalNameWithoutExtension(): string
    {
        return Util::parseNameParts($this->originalName())[0];
    }

    public function originalExtension(): ?string
    {
        if ($this->originalFile instanceof UploadedFile) {
            return Util::parseNameParts($this->originalFile->getClientOriginalName())[1];
        }

        return $this->originalFile->extension();
    }

    protected function inner(): Image
    {
        if (isset($this->inner)) {
            return $this->inner;
        }

        if (!isset(self::$filesystems)) {
            self::$filesystems = new FilesystemRegistry();
        }

        $file = $this->file();

        $filesystem = self::$filesystems->get($dir = \dirname($file), fn() => new AdapterFilesystem($dir));

        return $this->inner = $filesystem->file($file->getFilename())->ensureImage($this->config);
    }

    private function file(): SplFileInfo
    {
        return $this->file ??= ($this->generator)();
    }
}
