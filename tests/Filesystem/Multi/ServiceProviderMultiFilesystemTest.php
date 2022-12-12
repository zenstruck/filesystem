<?php

namespace Zenstruck\Tests\Filesystem\Multi;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Tests\Filesystem\MultiFilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ServiceProviderMultiFilesystemTest extends MultiFilesystemTest
{
    protected function createMultiFilesystem(array $filesystems, ?string $default = null): MultiFilesystem
    {
        $filesystems = \array_map(fn($f) => fn() => $f, $filesystems);

        return new MultiFilesystem(new ServiceLocator($filesystems), $default);
    }
}
