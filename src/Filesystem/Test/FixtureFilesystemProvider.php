<?php

namespace Zenstruck\Filesystem\Test;

use Zenstruck\Filesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface FixtureFilesystemProvider
{
    public function fixtureFilesystem(): string|Filesystem;
}
