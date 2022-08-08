<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\LazyFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class FileType extends StringType
{
    public const NAME = File::class;

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return $value instanceof File ? $value->path() : null;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?File
    {
        return \is_string($value) ? new LazyFile($value) : null;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
