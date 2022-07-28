<?php

namespace Zenstruck\Filesystem\Test;

use Zenstruck\Filesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface TestFilesystemProvider
{
    public function getTestFilesystem(): string|Filesystem;
}
