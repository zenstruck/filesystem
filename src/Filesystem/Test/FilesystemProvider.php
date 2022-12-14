<?php

namespace Zenstruck\Filesystem\Test;

use League\Flysystem\FilesystemAdapter;
use Zenstruck\Filesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface FilesystemProvider
{
    public function createFilesystem(): Filesystem|FilesystemAdapter|string;
}
