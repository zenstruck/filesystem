<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Test;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\InMemory\StaticInMemoryAdapterRegistry;
use League\Flysystem\ReadOnly\ReadOnlyFilesystemAdapter;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Flysystem\AdapterFactory;
use Zenstruck\Filesystem\FlysystemFilesystem;
use Zenstruck\Filesystem\MultiFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait InteractsWithFilesystem
{
    private TestFilesystem $_testFilesystem;

    /**
     * @before
     * @internal
     */
    public function _unsetFilesystem(): void
    {
        if (\class_exists(StaticInMemoryAdapterRegistry::class)) {
            StaticInMemoryAdapterRegistry::deleteAllFilesystems();
        }

        unset($this->_testFilesystem);
    }

    protected function filesystem(): TestFilesystem
    {
        if (isset($this->_testFilesystem)) {
            return $this->_testFilesystem;
        }

        if ($this instanceof FilesystemProvider) {
            $filesystem = $this->createFilesystem();

            if (!$filesystem instanceof Filesystem) {
                $filesystem = new FlysystemFilesystem($filesystem);
            }
        } elseif ($this instanceof KernelTestCase) {
            try {
                $filesystem = self::getContainer()->get(MultiFilesystem::class);
            } catch (NotFoundExceptionInterface $e) {
                throw new \LogicException('Could not get the filesystem from the service container, is the zenstruck/filesystem bundle enabled?', previous: $e);
            }
        } else {
            if (!\class_exists(InMemoryFilesystemAdapter::class)) {
                throw new \LogicException(\sprintf('league/flysystem-memory is required to use "%s". Install with "composer require --dev league/flysystem-memory".', __TRAIT__));
            }

            $filesystem = new FlysystemFilesystem(new InMemoryFilesystemAdapter());
        }

        if ($this instanceof FixtureFilesystemProvider) {
            $fixtures = $this->createFixtureFilesystem();

            if (\is_string($fixtures)) {
                $fixtures = AdapterFactory::createAdapter($fixtures);
            }

            if ($fixtures instanceof FilesystemAdapter && \class_exists(ReadOnlyFilesystemAdapter::class)) {
                $fixtures = new ReadOnlyFilesystemAdapter($fixtures);
            }

            if ($fixtures instanceof FilesystemAdapter) {
                $fixtures = new FlysystemFilesystem($fixtures);
            }

            $filesystem = new MultiFilesystem(['_default_' => $filesystem, 'fixture' => $fixtures], '_default_');
        }

        return $this->_testFilesystem = new TestFilesystem($filesystem);
    }
}
