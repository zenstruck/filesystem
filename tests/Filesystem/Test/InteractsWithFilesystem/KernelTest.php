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
        $filesystem = $this->filesystem();
        $filesystem->write('private://some/file1.txt', 'content');
        $filesystem->write('no_reset://some/file2.txt', 'content');

        $filesystem->assertExists('private://some/file1.txt');
        $filesystem->assertExists('no_reset://some/file2.txt');

        $this->_resetFilesystems();

        $filesystem->assertNotExists('private://some/file1.txt');
        $filesystem->assertExists('no_reset://some/file2.txt');
    }

    /**
     * @test
     */
    public function filesystem_persists_between_kernel_reboots(): void
    {
        $filesystem = $this->filesystem();
        $filesystem->write('private://some/file1.txt', 'content');

        $filesystem->assertExists('private://some/file1.txt');

        self::ensureKernelShutdown();

        $filesystem->assertExists('private://some/file1.txt');
    }
}
