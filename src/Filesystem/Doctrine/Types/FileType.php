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
use Doctrine\DBAL\Types\StringType;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Node\File\PlaceholderFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class FileType extends StringType
{
    public const NAME = 'zs_file';

    final public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof PendingFile) {
            throw new \LogicException('A pending file cannot be added directly to the database - use the event listener.');
        }

        if ($value instanceof PlaceholderFile) {
            throw new \LogicException('A placeholder file cannot be added to the database.');
        }

        return $value instanceof File ? $value->path() : null;
    }

    final public function convertToPHPValue($value, AbstractPlatform $platform): ?File
    {
        return \is_string($value) ? $this->createFile($value) : null;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    final public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    protected function createFile(string $path): LazyFile
    {
        return new LazyFile($path);
    }
}
