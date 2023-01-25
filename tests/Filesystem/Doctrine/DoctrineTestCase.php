<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemAdapter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Test\FixtureFilesystemProvider;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;
use Zenstruck\Filesystem\Test\ResetFilesystem;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Tests\Filesystem\Symfony\Fixture\Service;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class DoctrineTestCase extends KernelTestCase implements FixtureFilesystemProvider
{
    use Factories, InteractsWithFilesystem, ResetDatabase, ResetFilesystem;

    public function createFixtureFilesystem(): Filesystem|FilesystemAdapter|string
    {
        return FIXTURE_DIR;
    }

    protected function em(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }

    protected function loadMappingFor(object $object): object
    {
        return (self::getContainer()->get(Service::class)->objectFileLoader)($object);
    }
}
