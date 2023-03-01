<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Test\Node\Foundry;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Filesystem\Node\File\Image\PendingImage;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Test\Node\Foundry\LazyMock;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Test\Factories;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyMockTest extends KernelTestCase
{
    use Factories;

    /**
     * @test
     */
    public function pending_file(): void
    {
        $file = LazyMock::pendingFile('a-temp-file.txt');

        $this->assertFileDoesNotExist('/tmp/a-temp-file.txt');

        $file = $file();

        $this->assertInstanceOf(PendingFile::class, $file);

        $this->assertFileExists('/tmp/a-temp-file.txt');
    }

    /**
     * @test
     */
    public function pending_image(): void
    {
        $file = LazyMock::pendingImage(filename: 'a-temp-image.jpg');

        $this->assertFileDoesNotExist('/tmp/a-temp-image.jpg');

        $file = $file();

        $this->assertInstanceOf(PendingImage::class, $file);

        $this->assertFileExists('/tmp/a-temp-image.jpg');
    }

    /**
     * @test
     */
    public function faker_provider(): void
    {
        $this->assertInstanceOf(LazyValue::class, Factory::faker()->pendingFile());
        $this->assertInstanceOf(LazyValue::class, Factory::faker()->pendingImage());
    }
}
