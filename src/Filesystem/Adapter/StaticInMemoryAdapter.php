<?php

namespace Zenstruck\Filesystem\Adapter;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class StaticInMemoryAdapter extends WrappedAdapter
{
    /** @var InMemoryFilesystemAdapter[] */
    private static array $adapters = [];

    public function __construct(private string $name = 'default')
    {
        self::ensureSupported();
    }

    public static function ensureSupported(): void
    {
        if (!\class_exists(InMemoryFilesystemAdapter::class)) {
            throw new \LogicException(\sprintf('league/flysystem-memory is required to use %s. Install with "composer require (--dev) league/flysystem-memory".', self::class));
        }
    }

    public static function reset(): void
    {
        self::$adapters = [];
    }

    protected function inner(): FilesystemAdapter
    {
        return self::$adapters[$this->name] ??= new InMemoryFilesystemAdapter();
    }
}
