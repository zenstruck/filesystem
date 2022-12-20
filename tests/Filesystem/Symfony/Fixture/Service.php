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
use Zenstruck\Filesystem\MultiFilesystem;

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
        public MultiFilesystem $multiFilesystem
    ) {
    }
}
