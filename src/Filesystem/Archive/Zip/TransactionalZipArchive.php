<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Archive\Zip;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class TransactionalZipArchive extends \ZipArchive
{
    public function close(): bool
    {
        return true; // noop
    }

    public function commit(?callable $callback = null): bool
    {
        if ($callback) {
            $this->registerProgressCallback(0.01, $callback);
        }

        return parent::close();
    }
}
