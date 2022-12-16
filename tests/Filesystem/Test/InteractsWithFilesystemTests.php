<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Test;

use Zenstruck\Filesystem\Test\InteractsWithFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait InteractsWithFilesystemTests
{
    use InteractsWithFilesystem;

    /**
     * @test
     */
    public function can_get_filesystem(): void
    {
        $this->assertSame($this->filesystem(), $this->filesystem());

        $this->filesystem()->write('file.txt', 'content')
            ->last()
            ->ensureFile()
            ->assertContentIs('content')
        ;
    }

    /**
     * @test
     */
    public function created_filesystem_is_purged(): void
    {
        $filesystem = $this->filesystem()->write('file.txt', 'content');

        $filesystem->assertExists('file.txt');

        $this->_resetFilesystems();

        $filesystem->assertNotExists('file.txt');
    }
}
