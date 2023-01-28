<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\JsonType as BaseJsonType;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Node\File\PlaceholderFile;
use Zenstruck\Filesystem\Node\File\SerializableFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
abstract class JsonType extends BaseJsonType
{
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof PendingFile) {
            throw new \LogicException('A pending file cannot be added directly to the database - use the event listener.');
        }

        if ($value instanceof PlaceholderFile) {
            throw new \LogicException('A placeholder file cannot be added to the database.');
        }

        if (!$value instanceof SerializableFile) {
            throw ConversionException::conversionFailedInvalidType($value, SerializableFile::class, [SerializableFile::class, 'null']);
        }

        return parent::convertToDatabaseValue($value->serialize(), $platform);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?LazyFile
    {
        if (!$value = parent::convertToPHPValue($value, $platform)) {
            return null;
        }

        if (!\is_array($value)) {
            throw ConversionException::conversionFailedFormat($value, File::class, 'array|null');
        }

        return $this->dataToFile($value);
    }

    final public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    abstract protected function dataToFile(array $data): LazyFile;
}
