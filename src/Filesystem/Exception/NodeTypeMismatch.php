<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Exception;

use League\Flysystem\FilesystemException;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NodeTypeMismatch extends \RuntimeException implements FilesystemException
{
    /**
     * @internal
     */
    public static function expectedDirectoryAt(string $path): self
    {
        return new self(\sprintf('Expected node at path "%s" to be a directory but is a file.', $path));
    }

    /**
     * @internal
     */
    public static function expectedFileAt(string $path): self
    {
        return new self(\sprintf('Expected node at path "%s" to be a file but is a directory.', $path));
    }
}
