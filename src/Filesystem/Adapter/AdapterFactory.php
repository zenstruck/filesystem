<?php

namespace Zenstruck\Filesystem\Adapter;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
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
            'ftp', 'ftps' => self::createFtpAdapter($dsn),
            'in-memory' => self::createInMemoryAdapter($dsn, $name ?? $dsn->fragment() ?? 'default'),
            default => new LocalAdapter($dsn->path()->absolute()),
        };
    }

    private static function createFtpAdapter(Uri $dsn): FilesystemAdapter
    {
        if (!\class_exists(FtpAdapter::class)) {
            throw new \LogicException('league/flysystem-ftp is required to use the FTP adapter. Install with "composer require league/flysystem-ftp".');
        }

        return new FtpAdapter(FtpConnectionOptions::fromArray([
            'host' => $dsn->host()->toString(),
            'root' => $dsn->path()->absolute(),
            'username' => $dsn->user(),
            'password' => $dsn->pass(),
            'port' => $dsn->guessPort(),
            'ssl' => $dsn->query()->getBool('ssl', $dsn->scheme()->equals('ftps')),
        ]));
    }

    private static function createInMemoryAdapter(Uri $dsn, string $name): FilesystemAdapter
    {
        StaticInMemoryAdapter::ensureSupported();

        return $dsn->query()->has('static') ? new StaticInMemoryAdapter($name) : new InMemoryFilesystemAdapter();
    }
}
