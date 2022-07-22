<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\FileCollection;
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Filesystem\Node\File\LazyFileCollection;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
class FileCollectionType extends JsonType
{
    public const NAME = 'file_collection';

    final public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof FileCollection) {
            $value = \array_values(\array_map(static fn(File $file) => $file->path(), $value->all()));
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    /**
     * @return FileCollection<File>|null
     */
    final public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?FileCollection
    {
        $value = parent::convertToPHPValue($value, $platform);

        if (!\is_array($value)) {
            return null;
        }

        return new LazyFileCollection(\array_map(
            [static::class, 'createFileFor'],
            \array_filter($value, static fn(mixed $path) => \is_string($path))
        ));
    }

    final public function getName(): string
    {
        return static::NAME;
    }

    protected static function createFileFor(string $path): File
    {
        return new LazyFile($path);
    }
}
