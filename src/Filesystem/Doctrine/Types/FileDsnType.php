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

use Doctrine\DBAL\Types\StringType;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\LazyFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class FileDsnType extends StringType
{
    use Type;

    public const NAME = 'file_dsn';

    protected function fileToData(File $file): array|string
    {
        return $file->dsn();
    }

    protected function dataToFile(array|string $data): LazyFile
    {
        return new LazyFile($data);
    }
}
