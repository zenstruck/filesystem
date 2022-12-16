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

use League\Flysystem\UnableToDeleteFile;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Test\FixtureFilesystemProvider;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class WithFixturesUnitTest extends UnitTest implements FixtureFilesystemProvider
{
    /**
     * @test
     */
    public function can_access_fixtures(): void
    {
        $this->filesystem()->assertFileExists('fixture://symfony.png');
        $this->filesystem()->copy('fixture://symfony.png', 'file.png');
        $this->filesystem()->assertFileExists('file.png');
        $this->filesystem()->assertSame('file.png', 'fixture://symfony.png');
    }

    /**
     * @test
     */
    public function cannot_modify_fixtures(): void
    {
        $this->expectException(UnableToDeleteFile::class);
        $this->expectExceptionMessage('This is a readonly adapter');

        $this->filesystem()->move('fixture://symfony.png', 'file.png');
    }

    public function createFixtureFilesystem(): Filesystem|string
    {
        return FIXTURE_DIR;
    }
}
