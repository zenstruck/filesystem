<?php

namespace Zenstruck\Filesystem\Node\File;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Filesystem\Adapter\Operator;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PendingFile extends File
{
    private \SplFileInfo $file;

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

    public static function unserialize(string $serialized, MultiFilesystem $filesystem): File
    {
        throw new \BadMethodCallException(\sprintf('%s cannot be unserialized.', self::class));
    }

    public function serialize(): string
    {
        throw new \BadMethodCallException(\sprintf('%s cannot be serialized.', self::class));
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
