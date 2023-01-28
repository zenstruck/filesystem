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
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Node\File\PlaceholderFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait Type
{
    public function convertToDatabaseValue($value, AbstractPlatform $platform): string|array|null
    {
        if ($value instanceof PendingFile) {
            throw new \LogicException('A pending file cannot be added directly to the database - use the event listener.');
        }

        if ($value instanceof PlaceholderFile) {
            throw new \LogicException('A placeholder file cannot be added to the database.');
        }

        return $value instanceof File ? $this->fileToData($value) : null;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?LazyFile
    {
        return \is_string($value) ? $this->dataToFile($value) : null;
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    abstract protected function fileToData(File $file): array|string;

    abstract protected function dataToFile(string|array $data): LazyFile;
}
