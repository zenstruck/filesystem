<?php

namespace Zenstruck\Filesystem\Test;

use League\Flysystem\InMemory\StaticInMemoryAdapterRegistry;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Filesystem\ReadonlyFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait InteractsWithFilesystem
{
    private TestFilesystem $_testFilesystem;

    /**
     * @before
     *
     * @internal
     */
    public function _resetFilesystems(): void
    {
        if (\class_exists(StaticInMemoryAdapterRegistry::class)) {
            StaticInMemoryAdapterRegistry::deleteAllFilesystems();
        }

        unset($this->_testFilesystem);

        if ($this instanceof KernelTestCase && !$this instanceof TestFilesystemProvider) {
            // delete test filesystems
            // todo add option to disable this
            // todo on first test, detect if all test filesystems are (static) in-memory and disable
            if (self::getContainer()->hasParameter('zenstruck_filesystem.test_filesystems')) {
                // delete all test filesystems
                foreach (self::getContainer()->getParameter('zenstruck_filesystem.test_filesystems') as $id) {
                    self::getContainer()->get($id)->delete(Filesystem::ROOT);
                }
            }
        }
    }

    protected function filesystem(): TestFilesystem
    {
        if (isset($this->_testFilesystem)) {
            return $this->_testFilesystem;
        }

        if ($this instanceof TestFilesystemProvider) {
            $filesystem = $this->getTestFilesystem();
            $filesystem = \is_string($filesystem) ? new AdapterFilesystem($filesystem) : $filesystem;
        } elseif ($this instanceof KernelTestCase) {
            try {
                $filesystem = self::getContainer()->get(MultiFilesystem::class);
            } catch (NotFoundExceptionInterface $e) {
                throw new \LogicException('Could not get the filesystem from the service container, is the zenstruck/filesystem bundle enabled?', previous: $e);
            }
        } else {
            if (!\class_exists(StaticInMemoryAdapterRegistry::class)) {
                throw new \LogicException(\sprintf('league/flysystem-memory v3.3+ is required to use "%s". Install with "composer require --dev league/flysystem-memory".', __TRAIT__));
            }

            $filesystem = new AdapterFilesystem(StaticInMemoryAdapterRegistry::get());
        }

        if ($this instanceof FixtureFilesystemProvider) {
            $fixtures = $this->getFixtureFilesystem();
            $fixtures = new ReadonlyFilesystem(\is_string($fixtures) ? new AdapterFilesystem($fixtures) : $fixtures);

            $filesystem = new MultiFilesystem(['_default_' => $filesystem, 'fixture' => $fixtures], '_default_');
        }

        return $this->_testFilesystem = new TestFilesystem($filesystem);
    }
}
