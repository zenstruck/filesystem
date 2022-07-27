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

    public static function isSupported(): bool
    {
        return \class_exists(InMemoryFilesystemAdapter::class);
    }

    public static function ensureSupported(): void
    {
        if (!self::isSupported()) {
            throw new \LogicException('league/flysystem-memory is required to use the in-memory adapters. Install with "composer require --dev league/flysystem-memory".');
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
