<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Symfony\Fixture;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Doctrine\FileMappingLoader;
use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Filesystem\Node\File\PathGenerator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Service
{
    public function __construct(
        public Filesystem $filesystem,
        public Filesystem $publicFilesystem,
        public Filesystem $privateFilesystem,
        public Filesystem $noResetFilesystem,
        public MultiFilesystem $multiFilesystem,
        public PathGenerator $pathGenerator,
        public FileMappingLoader $objectFileLoader,
    ) {
    }
}
