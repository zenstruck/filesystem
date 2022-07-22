<?php

namespace Zenstruck\Filesystem\Tests;

use Zenstruck\Filesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class MultiFilesystemTest extends FilesystemTest
{
    /**
     * @test
     */
    public function can_nest_multi_filesystems(): void
    {
        $this->markTestIncomplete();
    }

    protected function createFilesystem(): Filesystem
    {
        $this->markTestIncomplete();
    }
}
