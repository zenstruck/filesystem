<?php

namespace Zenstruck\Filesystem\Adapter\ZipArchive;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class TransactionalZipArchive extends \ZipArchive
{
    public function __construct()
    {
    }

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
