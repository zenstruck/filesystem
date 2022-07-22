<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\LazyImage;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class ImageType extends StringType
{
    public const NAME = 'image';

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return $value instanceof Image ? $value->path() : null;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Image
    {
        return \is_string($value) ? new LazyImage($value) : null;
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
