<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Symfony;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;
use Zenstruck\Tests\Filesystem\Symfony\Fixture\Service;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckFilesystemBundleTest extends KernelTestCase
{
    use InteractsWithFilesystem;

    /**
     * @test
     */
    public function filesystem_services_autowired(): void
    {
        /** @var Service $service */
        $service = self::getContainer()->get(Service::class);

        $this->assertSame($service->filesystem, $service->publicFilesystem);
        $this->assertNotSame($service->filesystem, $service->privateFilesystem);

        $service->multiFilesystem->write('public://file1.txt', 'public content');
        $service->multiFilesystem->write('private://file2.txt', 'private content');

        $this->assertTrue($service->multiFilesystem->has('file1.txt'));
        $this->assertTrue($service->multiFilesystem->has('public://file1.txt'));
        $this->assertFalse($service->publicFilesystem->has('file2.txt'));
        $this->assertTrue($service->publicFilesystem->has('file1.txt'));
        $this->assertTrue($service->privateFilesystem->has('file2.txt'));
        $this->assertFalse($service->privateFilesystem->has('file1.txt'));
    }

    /**
     * @test
     */
    public function files_are_created_in_proper_spots(): void
    {
        /** @var Service $service */
        $service = self::getContainer()->get(Service::class);
        $varDir = \dirname(TEMP_DIR);

        $this->assertFileDoesNotExist($publicFile = $varDir.'/public/file1.txt');
        $this->assertFileDoesNotExist($privateFile = $varDir.'/private/file2.txt');
        $this->assertFileDoesNotExist($noResetFile = $varDir.'/no_reset/file3.txt');

        $service->publicFilesystem->write('file1.txt', 'content');
        $service->privateFilesystem->write('file2.txt', 'content');
        $service->noResetFilesystem->write('file3.txt', 'content');

        $this->assertFileExists($publicFile);
        $this->assertFileExists($privateFile);
        $this->assertFileExists($noResetFile);
    }
}
