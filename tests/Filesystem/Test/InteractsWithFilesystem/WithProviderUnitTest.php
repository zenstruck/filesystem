<?php

namespace Zenstruck\Tests\Filesystem\Test\InteractsWithFilesystem;

use League\Flysystem\FilesystemAdapter;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Test\FilesystemProvider;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class WithProviderUnitTest extends UnitTest implements FilesystemProvider
{
    public function createFilesystem(): Filesystem|FilesystemAdapter|string
    {
        return TEMP_DIR;
    }
}
