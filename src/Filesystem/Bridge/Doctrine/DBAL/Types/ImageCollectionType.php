<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Zenstruck\Filesystem\Node\File\FileCollection;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\LazyImage;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @method FileCollection<Image>|null convertToPHPValue(mixed $value, AbstractPlatform $platform)
 */
final class ImageCollectionType extends FileCollectionType
{
    public function getName(): string
    {
        return 'image_collection';
    }

    protected static function createFileFor(string $path): Image
    {
        return new LazyImage($path);
    }
}
