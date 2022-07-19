<?php

namespace Zenstruck\Filesystem\Exception;

use League\Flysystem\FilesystemException;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NodeNotFound extends \RuntimeException implements FilesystemException
{
    private function __construct(string $path)
    {
        parent::__construct(\sprintf('Node not found for path "%s".', $path));
    }

    public static function for(string $path): self
    {
        return new self($path);
    }
}
