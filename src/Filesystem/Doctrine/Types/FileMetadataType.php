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

use Doctrine\DBAL\Types\JsonType;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Filesystem\Node\File\SerializableFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class FileMetadataType extends JsonType
{
    use Type;

    public const NAME = 'file_metadata';

    protected function fileToData(File $file): array|string
    {
        if (!$file instanceof SerializableFile) {
            throw new \LogicException('Invalid mapping.');
        }

        return $file->serialize();
    }

    protected function dataToFile(array|string $data): LazyFile
    {
        return new LazyFile($data);
    }
}
