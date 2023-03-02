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
use Doctrine\DBAL\Types\StringType as BaseStringType;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Node\File\PlaceholderFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
abstract class StringType extends BaseStringType
{
    final public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
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

        if (!$value instanceof File) {
            throw ConversionException::conversionFailedInvalidType($value, File::class, [File::class, 'null']);
        }

        return $this->fileToData($value);
    }

    final public function convertToPHPValue($value, AbstractPlatform $platform): ?LazyFile
    {
        return \is_string($value) ? $this->dataToFile($value) : null;
    }

    final public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    abstract protected function fileToData(File $file): string;

    abstract protected function dataToFile(string $data): LazyFile;
}
