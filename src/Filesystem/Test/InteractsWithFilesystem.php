<?php

namespace Zenstruck\Filesystem\Test;

use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Flysystem\Adapter\StaticInMemoryAdapter;
use Zenstruck\Filesystem\FlysystemFilesystem;
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
        StaticInMemoryAdapter::reset();

        unset($this->_testFilesystem);
    }

    protected function filesystem(): TestFilesystem
    {
        if (isset($this->_testFilesystem)) {
            return $this->_testFilesystem;
        }

        if ($this instanceof KernelTestCase) {
            try {
                $filesystem = self::getContainer()->get(Filesystem::class);
            } catch (NotFoundExceptionInterface $e) {
                throw new \LogicException('Could not get the filesystem from the service container, is the zenstruck/filesystem bundle enabled?', previous: $e);
            }
        } else {
            $filesystem = new FlysystemFilesystem(new StaticInMemoryAdapter());
        }

        if ($this instanceof FixtureFilesystemProvider) {
            $fixtures = $this->fixtureFilesystem();
            $fixtures = new ReadonlyFilesystem(\is_string($fixtures) ? new FlysystemFilesystem($fixtures) : $fixtures);

            $filesystem = new MultiFilesystem(['_default_' => $filesystem, 'fixture' => $fixtures], '_default_');
        }

        return $this->_testFilesystem = new TestFilesystem($filesystem);
    }
}
