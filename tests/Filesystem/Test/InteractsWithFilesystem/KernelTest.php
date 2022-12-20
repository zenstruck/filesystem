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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Tests\Filesystem\Test\InteractsWithFilesystemTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class KernelTest extends KernelTestCase
{
    use InteractsWithFilesystemTests;

    /**
     * @test
     */
    public function filesystems_are_deleted_before_each_test(): void
    {
        $this->filesystem()->write('private://some/file1.txt', 'content');
        $this->filesystem()->write('no_reset://some/file2.txt', 'content');

        $this->filesystem()->assertExists('private://some/file1.txt');
        $this->filesystem()->assertExists('no_reset://some/file2.txt');

        $this->_resetFilesystems();

        $this->filesystem()->assertNotExists('private://some/file1.txt');
        $this->filesystem()->assertExists('no_reset://some/file2.txt');
    }
}
