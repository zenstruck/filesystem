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

use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\LazyFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class FilePathType extends StringType
{
    public const NAME = 'file_path';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function fileToData(File $file): string
    {
        return $file->path();
    }

    protected function dataToFile(string $data): LazyFile
    {
        return new LazyFile($data);
    }
}
