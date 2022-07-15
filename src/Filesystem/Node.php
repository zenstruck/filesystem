<?php

namespace Zenstruck\Filesystem;

use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Node
{
    final public function isDirectory(): bool
    {
        return $this instanceof Directory;
    }

    final public function isFile(): bool
    {
        return $this instanceof File;
    }

    final public function ensureDirectory(): Directory
    {
        return $this instanceof Directory ? $this : throw new \RuntimeException('Not a directory.'); // TODO add path
    }

    final public function ensureFile(): File
    {
        return $this instanceof File ? $this : throw new \RuntimeException('Not a file.'); // TODO add path
    }
}
