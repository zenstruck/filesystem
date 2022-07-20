<?php

namespace Zenstruck\Filesystem\Test;

use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Flysystem\Adapter\StaticInMemoryAdapter;
use Zenstruck\Filesystem\FlysystemFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait InteractsWithFilesystem
{
    private Filesystem $_testFilesystem;

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

    public function filesystem(): TestFilesystem
    {
        if (!$this instanceof KernelTestCase) {
            return $this->_testFilesystem ??= new TestFilesystem(new FlysystemFilesystem(new StaticInMemoryAdapter()));
        }

        try {
            return $this->_testFilesystem ??= self::getContainer()->get(Filesystem::class);
        } catch (NotFoundExceptionInterface $e) {
            throw new \LogicException('Could not get the filesystem from the service container, is the zenstruck/filesystem bundle enabled?', previous: $e);
        }
    }
}
