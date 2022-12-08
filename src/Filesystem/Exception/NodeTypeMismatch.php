<?php

namespace Zenstruck\Filesystem\Exception;

use League\Flysystem\FilesystemException;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NodeTypeMismatch extends \RuntimeException implements FilesystemException
{
    public static function expectedDirectoryAt(string $path): self
    {
        return new self(\sprintf('Expected node at path "%s" to be a directory but is a file.', $path));
    }

    public static function expectedFileAt(string $path): self
    {
        return new self(\sprintf('Expected node at path "%s" to be a file but is a directory.', $path));
    }
}
