<?php

namespace Zenstruck\Filesystem\Exception;

use League\Flysystem\FilesystemException;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NodeNotFound extends \RuntimeException implements FilesystemException
{
    public function __construct(string $path, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('Node at path "%s" not found.', $path), previous: $previous);
    }
}
