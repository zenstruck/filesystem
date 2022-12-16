<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
