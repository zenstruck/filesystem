<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Node\Directory;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Test\Node\TestDirectory;
use Zenstruck\Tests\Filesystem\Node\DirectoryTests;
use Zenstruck\Tests\InteractsWithTempDirectory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FlysystemDirectoryTest extends TestCase
{
    use DirectoryTests, InteractsWithTempDirectory;

    /**
     * @test
     */
    public function date_range_filter(): void
    {
        $dir = self::forDateDirectory();

        $dir
            ->date('before 2 hours ago')
            ->assertCount(0)
        ;

        $dir
            ->date('after 2 hours ago')
            ->assertCount(2)
            ->date('before 30 minutes ago')
            ->assertCount(1)
        ;

        $dir
            ->olderThan(new \DateTime('2 hours ago'))
            ->assertCount(0)
        ;

        $dir
            ->newerThan(new \DateTime('2 hours ago'))
            ->assertCount(2)
            ->olderThan('30 minutes ago')
            ->assertCount(1)
        ;
    }

    protected function createDirectory(\SplFileInfo $directory, string $path): Directory
    {
        return in_memory_filesystem()->write($path, $directory)->directory($path);
    }

    private static function forDateDirectory(): TestDirectory
    {
        $filesystem = temp_filesystem()
            ->write('foo.txt', 'foo')
            ->write('bar/baz.txt', 'baz')
        ;

        \touch(tempfile('foo.txt'), (new \DateTime('-1 hour'))->getTimestamp());
        \touch(tempfile('bar/baz.txt'), (new \DateTime('-15 minutes'))->getTimestamp());

        return (new TestDirectory($filesystem->directory()))->recursive()->files();
    }
}
