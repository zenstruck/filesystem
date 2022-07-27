<?php

namespace Zenstruck\Filesystem\Adapter;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AdapterFactory
{
    public function create(string|Uri $dsn, ?string $name = null): FilesystemAdapter
    {
        $dsn = Uri::new($dsn);

        return match ($dsn->scheme()->toString()) {
            'in-memory' => self::createInMemoryFilesystem($dsn, $name ?? $dsn->fragment() ?? 'default'),
            default => new LocalAdapter($dsn->path()->absolute()),
        };
    }

    private static function createInMemoryFilesystem(Uri $dsn, string $name): FilesystemAdapter
    {
        StaticInMemoryAdapter::ensureSupported();

        return $dsn->query()->has('static') ? new StaticInMemoryAdapter($name) : new InMemoryFilesystemAdapter();
    }
}
