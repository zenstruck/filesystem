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

use Zenstruck\Filesystem\Node\File\LazyFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class FileMetadataType extends JsonType
{
    public const NAME = 'file_metadata';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function dataToFile(array $data): LazyFile
    {
        return new LazyFile($data);
    }
}
