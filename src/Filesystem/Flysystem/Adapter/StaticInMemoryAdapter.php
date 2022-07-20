<?php

namespace Zenstruck\Filesystem\Flysystem\Adapter;

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
        if (!\class_exists(InMemoryFilesystemAdapter::class)) {
            throw new \LogicException(\sprintf('league/flysystem-memory is required to use %s. Install with "composer install (--dev) league/flysystem-memory".', self::class));
        }
    }

    public static function reset(): void
    {
        self::$adapters = [];
    }

    protected function inner(): InMemoryFilesystemAdapter
    {
        return self::$adapters[$this->name] ??= new InMemoryFilesystemAdapter();
    }
}
