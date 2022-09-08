<?php

namespace Zenstruck\Filesystem\Node\File;

use League\Flysystem\FileAttributes;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Mime\MimeTypesInterface;
use Zenstruck\Dimension\Information;
use Zenstruck\Filesystem\Adapter\Operator;
use Zenstruck\Filesystem\Node\AdapterNode;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\Directory\AdapterDirectory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image\AdapterImage;
use Zenstruck\Filesystem\Util;
use Zenstruck\Filesystem\Util\TempFile;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
class AdapterFile extends AdapterNode implements File
{
    private static MimeTypesInterface $mimeTypes;

    private Information $size;
    private string $mimeType;
    private Checksum $checksum;

    /** @var array{0:string,1:string|null} */
    private array $nameParts;

    /**
     * @internal
     */
    public function __construct(FileAttributes $attributes, Operator $operator)
    {
        parent::__construct($attributes, $operator);

        if ($size = $attributes->fileSize()) {
            $this->size = Information::binary($size);
        }

        if ($mimeType = $attributes->mimeType()) {
            $this->mimeType = $mimeType;
        }
    }

    /**
     * Create a temporary, "real, local file". This file is deleted at the
     * end of the script.
     */
    final public function tempFile(): \SplFileInfo
    {
        return TempFile::for($this);
    }

    /**
     * @see https://github.com/zenstruck/dimension#information-object
     */
    final public function size(): Information
    {
        return $this->size ??= Information::binary($this->operator()->fileSize($this->path()));
    }

    final public function mimeType(): string
    {
        return $this->mimeType ??= $this->operator()->mimeType($this->path());
    }

    final public function contents(): string
    {
        return $this->operator()->read($this->path());
    }

    final public function read()
    {
        return $this->operator()->readStream($this->path());
    }

    final public function checksum(): Checksum
    {
        return $this->checksum ??= new Checksum($this, $this->operator());
    }

    final public function contentsEquals(File $other): bool
    {
        return $this->checksum->equals($other->checksum());
    }

    final public function metadataEquals(File $other): bool
    {
        return $this->checksum->forMetadata()->equals($other->checksum());
    }

    final public function directory(): Directory
    {
        return new AdapterDirectory($this->operator()->directoryAttributesFor($this->dirname()), $this->operator());
    }

    final public function extension(): ?string
    {
        return $this->nameParts($this->name())[1];
    }

    final public function guessExtension(): ?string
    {
        if ($ext = $this->extension()) {
            return $ext;
        }

        if (!\interface_exists(MimeTypesInterface::class)) {
            return null;
        }

        return (self::$mimeTypes ??= new MimeTypes())->getExtensions($this->mimeType())[0] ?? null;
    }

    final public function nameWithoutExtension(): string
    {
        return $this->nameParts($this->name())[0];
    }

    final public function url(array $options = []): Uri
    {
        return $this->operator()->urlFor($this, $options);
    }

    public function refresh(): static
    {
        unset($this->size, $this->mimeType, $this->checksum);

        return parent::refresh();
    }

    final protected function castToImage(): AdapterImage
    {
        $image = new AdapterImage();

        if (isset($this->size)) {
            $image->size = $this->size;  // @phpstan-ignore-line
        }

        if (isset($this->mimeType)) {
            $image->mimeType = $this->mimeType;  // @phpstan-ignore-line
        }

        if (isset($this->checksum)) {
            $image->checksum = $this->checksum;  // @phpstan-ignore-line
        }

        if (isset($this->nameParts)) {
            $image->nameParts = $this->nameParts;  // @phpstan-ignore-line
        }

        return $image;
    }

    /**
     * @return array{0:string,1:string|null}
     */
    private function nameParts(string $filename): array
    {
        return $this->nameParts ??= Util::parseNameParts($filename);
    }
}
