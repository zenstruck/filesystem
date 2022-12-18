<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait InteractsWithTempDirectory
{
    /**
     * @before
     */
    public static function purgeTempDir(): void
    {
        temp_filesystem()->delete('');
        temp_filesystem();
    }
}
