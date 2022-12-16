<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Zenstruck\Filesystem\Exception\UnregisteredFilesystem;
use Zenstruck\Filesystem\FilesystemRegistry;
use Zenstruck\Filesystem\FlysystemFilesystem;
use Zenstruck\Filesystem\LazyFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FilesystemRegistryTest extends TestCase
{
    /**
     * @test
     */
    public function can_get_filesystem_from_array(): void
    {
        $registry = new FilesystemRegistry(['foo' => in_memory_filesystem()]);

        $this->assertInstanceOf(FlysystemFilesystem::class, $registry->get('foo'));
        $this->assertSame($registry->get('foo'), $registry->get('foo'));
    }

    /**
     * @test
     */
    public function invalid_filesystem_from_array(): void
    {
        $registry = new FilesystemRegistry([]);

        $this->expectException(UnregisteredFilesystem::class);

        $registry->get('invalid');
    }

    /**
     * @test
     */
    public function can_get_filesystem_from_container(): void
    {
        $count = 0;
        $registry = new FilesystemRegistry(new ServiceLocator([
            'foo' => function() use (&$count) {
                ++$count;

                return in_memory_filesystem();
            },
        ]));

        $this->assertInstanceOf(LazyFilesystem::class, $registry->get('foo'));
        $this->assertSame($registry->get('foo'), $registry->get('foo'));
        $this->assertSame(0, $count);
        $this->assertFalse($registry->get('foo')->has('path'));
        $this->assertFalse($registry->get('foo')->has('path'));
        $this->assertFalse($registry->get('foo')->has('path'));
        $this->assertSame(1, $count);
    }

    /**
     * @test
     */
    public function invalid_filesystem_from_container(): void
    {
        $registry = new FilesystemRegistry(new ServiceLocator([]));
        $filesystem = $registry->get('invalid');

        $this->assertInstanceOf(LazyFilesystem::class, $filesystem);

        $this->expectException(UnregisteredFilesystem::class);

        $filesystem->has('path');
    }

    /**
     * @test
     */
    public function resets_cache_if_container(): void
    {
        $count = 0;
        $registry = new FilesystemRegistry(new ServiceLocator([
            'foo' => function() use (&$count) {
                ++$count;

                return in_memory_filesystem();
            },
        ]));

        $this->assertSame(0, $count);
        $this->assertFalse($registry->get('foo')->has('path'));
        $this->assertFalse($registry->get('foo')->has('path'));
        $this->assertFalse($registry->get('foo')->has('path'));
        $this->assertSame(1, $count);

        $registry->reset();

        $this->assertFalse($registry->get('foo')->has('path'));
        $this->assertSame(2, $count);
    }

    /**
     * @test
     */
    public function does_not_reset_cache_if_array(): void
    {
        $registry = new FilesystemRegistry(['foo' => in_memory_filesystem()]);

        $this->assertInstanceOf(FlysystemFilesystem::class, $registry->get('foo'));

        $registry->reset();

        $this->assertInstanceOf(FlysystemFilesystem::class, $registry->get('foo'));
    }
}
