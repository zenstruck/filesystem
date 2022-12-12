<?php

namespace Zenstruck\Tests\Filesystem\Multi;

use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Tests\Filesystem\MultiFilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ArrayMultiFilesystemTest extends MultiFilesystemTest
{
    protected function createMultiFilesystem(array $filesystems, ?string $default = null): MultiFilesystem
    {
        return new MultiFilesystem($filesystems, $default);
    }
}
