<?php

namespace Zenstruck\Filesystem\Node;

use League\Flysystem\FileAttributes;
use Zenstruck\Dimension\Information;
use Zenstruck\Filesystem\Adapter\Operator;
use Zenstruck\Filesystem\Exception\UnsupportedFeature;
use Zenstruck\Filesystem\Feature\FileUrl;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\File\Checksum;
use Zenstruck\Filesystem\TempFile;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class File implements Node
{
    use IsNode {
        __construct as traitConstruct;
        refresh as traitRefresh;
    }

    protected const MULTI_EXTENSIONS = ['gz' => 'tar.gz', 'bz2' => 'tar.bz2'];

    /** @var array<string,Operator> */
    protected static array $localOperators = [];

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
        $this->traitConstruct($attributes, $operator);

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

    /**
     * @return resource
     */
    final public function read()
    {
        return $this->operator()->readStream($this->path());
    }

    /**
     * Calculate the checksum for the file. Defaults to md5.
     *
     * @example $file->checksum()->toString() (md5 hash of contents)
     * @example $file->checksum()->sha1()->toString() (sha1 hash of contents)
     * @example $file->checksum()->metadata()->toString() (md5 hash of file size + last modified timestamp + mime-type)
     * @example $file->checksum()->metadata()->sha1()->toString() (sha1 hash of file size + last modified timestamp + mime-type)
     */
    final public function checksum(): Checksum
    {
        return $this->checksum ??= new Checksum($this, $this->operator());
    }

    /**
     * Whether the contents of this file and another match (content checksum is used).
     */
    final public function contentsEquals(self $other): bool
    {
        return $this->checksum->equals($other->checksum());
    }

    /**
     * Whether the metadata of this file and another match (metadata checksum is used).
     */
    final public function metadataEquals(self $other): bool
    {
        return $this->checksum->forMetadata()->equals($other->checksum());
    }

    /**
     * @return Directory<Node>
     */
    final public function directory(): Directory
    {
        return new Directory($this->operator()->directoryAttributesFor($this->dirname()), $this->operator());
    }

    final public function extension(): ?string
    {
        return $this->nameParts($this->name())[1];
    }

    /**
     * @example If $path is "foo/bar/baz.txt", returns "baz"
     * @example If $path is "foo/bar/baz", returns "baz"
     */
    final public function nameWithoutExtension(): string
    {
        return $this->nameParts($this->name())[0];
    }

    /**
     * @param array<string,mixed> $options
     *
     * @throws UnsupportedFeature If your adapter does not support {@see FileUrl}
     */
    final public function url(array $options = []): Uri
    {
        return $this->operator()->urlFor($this, $options);
    }

    public function refresh(): static
    {
        unset($this->size, $this->mimeType, $this->checksum);

        return $this->traitRefresh();
    }

    /**
     * @return array{0:string,1:string|null}
     */
    protected static function parseNameParts(string $filename): array
    {
        if (!$ext = \mb_strtolower(\pathinfo($filename, \PATHINFO_EXTENSION)) ?: null) {
            return [$filename, null];
        }

        if (isset(self::MULTI_EXTENSIONS[$ext]) && \str_ends_with($filename, self::MULTI_EXTENSIONS[$ext])) {
            $ext = self::MULTI_EXTENSIONS[$ext];
        }

        return [\mb_substr($filename, 0, -(\mb_strlen($ext) + 1)), $ext];
    }

    /**
     * @return array{0:string,1:string|null}
     */
    private function nameParts(string $filename): array
    {
        return $this->nameParts ??= self::parseNameParts($filename);
    }
}
