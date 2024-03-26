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
use Doctrine\DBAL\Types\Exception\InvalidType;
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
    /**
     * @template T of ?string
     */
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

        if ($value instanceof SerializableFile) {
            return parent::convertToDatabaseValue($value->serialize(), $platform);
        }

        if (\class_exists(InvalidType::class)) {
            // dbal 4+
            throw InvalidType::new($value, SerializableFile::class, [SerializableFile::class, 'null']);
        }

        throw ConversionException::conversionFailedInvalidType($value, SerializableFile::class, [SerializableFile::class, 'null']); // @phpstan-ignore-line
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?LazyFile
    {
        if (!$value = parent::convertToPHPValue($value, $platform)) {
            return null;
        }

        if (\is_array($value)) {
            return $this->dataToFile($value);
        }

        if (\class_exists(InvalidType::class)) {
            // dbal 4+
            throw InvalidType::new($value, File::class, ['array', 'null']);
        }

        throw ConversionException::conversionFailedFormat($value, File::class, 'array|null'); // @phpstan-ignore-line
    }

    final public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    /**
     * @param array<string,mixed> $data
     */
    abstract protected function dataToFile(array $data): LazyFile;
}
