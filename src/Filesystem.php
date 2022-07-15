<?php

namespace Zenstruck;

use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface Filesystem
{
    public function node(string $path = ''): Node;

    public function file(string $path): File;

    /**
     * @return Directory<Node>
     */
    public function directory(string $path = ''): Directory;

    public function exists(string $path = ''): bool;

    public function copy(string $source, string $destination): void;

    public function move(string $source, string $destination): void;

    public function delete(string $path = ''): void;

    public function mkdir(string $path = ''): void;

    /**
     * @param resource|string|\SplFileInfo|Directory<Node>|File $value
     */
    public function write(string $path, mixed $value): void;
}
