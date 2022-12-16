<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Test;

use League\Flysystem\FilesystemAdapter;
use Zenstruck\Filesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface FixtureFilesystemProvider
{
    public function createFixtureFilesystem(): Filesystem|FilesystemAdapter|string;
}
