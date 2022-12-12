<?php

namespace Zenstruck\Tests\Filesystem\Multi;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Exception\UnregisteredFilesystem;
use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Tests\Filesystem\MultiFilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ContainerMultiFilesystemTest extends MultiFilesystemTest
{
    public function can_nest_multi_filesystems(): void
    {
        $this->expectException(UnregisteredFilesystem::class);

        parent::can_nest_multi_filesystems();
    }

    protected function createMultiFilesystem(array $filesystems, ?string $default = null): MultiFilesystem
    {
        $container = new class($filesystems) implements ContainerInterface {
            public function __construct(private array $filesystems)
            {
            }

            public function get(string $id): Filesystem
            {
                return $this->filesystems[$id] ?? throw new ServiceNotFoundException($id);
            }

            public function has(string $id): bool
            {
                return isset($this->filesystems[$id]);
            }
        };

        return new MultiFilesystem($container, $default);
    }
}
