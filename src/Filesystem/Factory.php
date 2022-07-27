<?php

namespace Zenstruck\Filesystem;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Zenstruck\Dsn\Parser;
use Zenstruck\Dsn\Parser\ChainParser;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Adapter\LocalAdapter;
use Zenstruck\Filesystem\Adapter\StaticInMemoryAdapter;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-import-type GlobalConfig from AdapterFilesystem
 */
final class Factory
{
    private Parser $parser;

    public function __construct(?Parser $parser = null)
    {
        $this->parser = $parser ?? new ChainParser();
    }

    /**
     * @param GlobalConfig|array<string,mixed> $config
     */
    public function create(string|\Stringable|FilesystemAdapter $dsn, ?string $name = null, array $config = []): Filesystem
    {
        if ($dsn instanceof FilesystemAdapter) {
            return new AdapterFilesystem($dsn, $config, $name ?? 'default');
        }

        if (\is_string($dsn)) {
            $dsn = $this->parser->parse($dsn);
        }

        if (!$dsn instanceof Uri) {
            throw new \InvalidArgumentException(\sprintf('Could not create filesystem from DSN "%s".', $dsn));
        }

        $name = $name ?? $dsn->fragment() ?? 'default';
        $adapter = match ($dsn->scheme()->toString()) {
            'in-memory' => self::createInMemoryFilesystem($dsn, $name),
            default => new LocalAdapter($dsn),
        };

        return new AdapterFilesystem($adapter, \array_merge($dsn->query()->all(), $config), $name);
    }

    private static function createInMemoryFilesystem(Uri $dsn, string $name): FilesystemAdapter
    {
        StaticInMemoryAdapter::ensureSupported();

        return $dsn->query()->has('static') ? new StaticInMemoryAdapter($name) : new InMemoryFilesystemAdapter();
    }
}
