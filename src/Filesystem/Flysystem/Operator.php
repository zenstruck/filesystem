<?php

namespace Zenstruck\Filesystem\Flysystem;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathNormalizer;
use Zenstruck\Filesystem\Feature\All;
use Zenstruck\Filesystem\Flysystem\Adapter\WrappedAdapter;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class Operator extends Filesystem implements All
{
    private WrappedAdapter $adapter;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(FilesystemAdapter $adapter, array $config = [], ?PathNormalizer $pathNormalizer = null)
    {
        if (!$adapter instanceof WrappedAdapter) {
            $adapter = new WrappedAdapter($adapter);
        }

        parent::__construct($this->adapter = $adapter, $config, $pathNormalizer);
    }

    public function supports(string $feature): bool
    {
        return $this->adapter->supports($feature);
    }

    public function md5Checksum(string $path): string
    {
        return $this->adapter->md5Checksum($path);
    }

    public function sha1Checksum(string $path): string
    {
        return $this->adapter->sha1Checksum($path);
    }
}
