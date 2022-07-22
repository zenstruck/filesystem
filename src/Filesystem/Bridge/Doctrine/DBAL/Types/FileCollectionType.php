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
 */
final class FileCollectionType extends JsonType
{
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof FileCollection) {
            $value = \array_values(\array_map(fn(File $file) => $file->path(), $value->all()));
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?FileCollection
    {
        $value = parent::convertToPHPValue($value, $platform);

        if (!\is_array($value)) {
            return null;
        }

        return new LazyFileCollection(\array_map(
            static fn(string $path): LazyFile => new LazyFile($path),
            \array_filter($value, static fn(mixed $path) => \is_string($path))
        ));
    }

    public function getName(): string
    {
        return 'file_collection';
    }
}
