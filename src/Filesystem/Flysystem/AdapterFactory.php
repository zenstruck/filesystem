<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Flysystem;

use AsyncAws\S3\S3Client as AsyncS3Client;
use Aws\S3\S3Client;
use League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\InMemory\StaticInMemoryAdapterRegistry;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\ReadOnly\ReadOnlyFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @todo make extensible with zenstruck/dsn
 * @internal
 */
final class AdapterFactory
{
    public static function createAdapter(string $dsn): FilesystemAdapter
    {
        $readonly = false;

        if (\str_starts_with($dsn, 'readonly:')) {
            $readonly = true;
            $dsn = \mb_substr($dsn, 9);
        }

        if ($readonly && !\class_exists(ReadOnlyFilesystemAdapter::class)) {
            throw new \LogicException('league/flysystem-read-only is required (composer require league/flysystem-read-only).');
        }

        $adapter = self::createRealAdapter($dsn);

        return $readonly ? new ReadOnlyFilesystemAdapter($adapter) : $adapter;
    }

    private static function createRealAdapter(string $dsn): FilesystemAdapter
    {
        $parsed = self::parse($dsn);

        return match ($parsed['scheme'] ?? null) {
            null, 'file' => self::createLocalAdapter($dsn, $parsed),
            'flysystem+ftp', 'flysystem+ftps' => self::createFtpAdapter($parsed),
            'flysystem+sftp' => self::createSftpAdapter($parsed),
            'flysystem+s3' => self::createS3Adapter($parsed),
            'in-memory' => self::createInMemoryAdapter($parsed),
            default => throw new \InvalidArgumentException(\sprintf('Could not create FilesystemAdapter for DSN "%s".', $dsn)),
        };
    }

    private static function createLocalAdapter(string $dsn, array $parsed): LocalFilesystemAdapter // @phpstan-ignore-line
    {
        $parsed = self::normalizeQuery($parsed);
        $visibility = $parsed['query']['visibility'] ?? [];

        return new LocalFilesystemAdapter(
            \explode('?', $dsn)[0],
            PortableVisibilityConverter::fromArray(
                $visibility,
                $visibility['default_for_directories'] ?? Visibility::PRIVATE
            )
        );
    }

    private static function parse(string $dsn): array // @phpstan-ignore-line
    {
        $parsed = \parse_url($dsn);

        // for some reason parse_url doesn't support stream wrapper schemes (like phar://) out of the box
        if (false === $parsed && \str_contains($dsn, '://') && false !== $parsed = \parse_url(\str_replace('://', ':', $dsn))) {
            unset($parsed['scheme']);
        }

        if (false === $parsed) {
            throw new \InvalidArgumentException(\sprintf('Could not parse DSN "%s".', $dsn));
        }

        if (isset($parsed['user'])) {
            $parsed['user'] = \rawurldecode($parsed['user']);
        }

        if (isset($parsed['pass'])) {
            $parsed['pass'] = \rawurldecode($parsed['pass']);
        }

        return $parsed;
    }

    private static function normalizeQuery(array $parts): array // @phpstan-ignore-line
    {
        $query = [];

        \parse_str($parts['query'] ??= '', $query);

        $parts['query'] = $query;

        return $parts;
    }

    private static function createInMemoryAdapter(array $parsed): InMemoryFilesystemAdapter // @phpstan-ignore-line
    {
        if ($name = $parsed['path'] ?? null) {
            return StaticInMemoryAdapterRegistry::get($name);
        }

        return new InMemoryFilesystemAdapter();
    }

    private static function createSftpAdapter(array $parsed): FilesystemAdapter // @phpstan-ignore-line
    {
        if (!\class_exists(SftpAdapter::class)) {
            throw new \LogicException('league/flysystem-sftp-v3 is required to use the SFTP adapter. Install with "composer require league/flysystem-sftp-v3".');
        }

        $parsed = self::normalizeQuery($parsed);

        return new SftpAdapter(
            new SftpConnectionProvider(
                host: $parsed['host'] ?? throw new \InvalidArgumentException('Host is required for SftpAdapter.'),
                username: $parsed['user'] ?? throw new \InvalidArgumentException('Username is required for SftpAdapter.'),
                password: $parsed['pass'] ?? null,
                privateKey: $parsed['query']['private-key'] ?? null,
                passphrase: $parsed['query']['passphrase'] ?? null,
                port: $parsed['port'] ?? 22,
            ),
            $parsed['path'] ?? throw new \InvalidArgumentException('Path is required for SftpAdapter.'),
        );
    }

    private static function createFtpAdapter(array $parsed): FilesystemAdapter // @phpstan-ignore-line
    {
        if (!\class_exists(FtpAdapter::class)) {
            throw new \LogicException('league/flysystem-ftp is required to use the FTP adapter. Install with "composer require league/flysystem-ftp".');
        }

        $parsed = self::normalizeQuery($parsed);

        return new FtpAdapter(FtpConnectionOptions::fromArray(\array_filter([
            'host' => $parsed['host'] ?? null,
            'root' => $parsed['path'] ?? null,
            'username' => $parsed['user'] ?? null,
            'password' => $parsed['pass'] ?? null,
            'port' => $parsed['port'] ?? 21,
            'ssl' => (bool) ($parsed['query']['ssl'] ?? 'flysystem+ftps' === ($parsed['scheme'] ?? null)),
        ])));
    }

    private static function createS3Adapter(array $parsed): FilesystemAdapter // @phpstan-ignore-line
    {
        $parsed = self::normalizeQuery($parsed);

        if (\class_exists(AsyncAwsS3Adapter::class)) {
            return new AsyncAwsS3Adapter(
                new AsyncS3Client([
                    'region' => $parsed['query']['region'] ?? $parsed['fragment'] ?? throw new \InvalidArgumentException('A region must be set in the query (ie ?region=us-east-1) or as the fragment (ie #us-east-1).'),
                    'accessKeyId' => $parsed['user'] ?? null,
                    'accessKeySecret' => $parsed['pass'] ?? null,
                ]),
                $parsed['host'] ?? throw new \InvalidArgumentException('A bucket must be set as the host.'), // bucket
                $parsed['path'] ?? '', // prefix
            );
        }

        if (\class_exists(AwsS3V3Adapter::class)) {
            return new AwsS3V3Adapter( // @phpstan-ignore-line
                new S3Client([ // @phpstan-ignore-line
                    'region' => $parsed['query']['region'] ?? $parsed['fragment'] ?? throw new \InvalidArgumentException('A region must be set in the query (ie ?region=us-east-1) or as the fragment (ie #us-east-1).'),
                    'credentials' => [
                        'key' => $parsed['user'] ?? null,
                        'secret' => $parsed['pass'] ?? null,
                    ],
                    'version' => $parsed['query']['version'] ?? 'latest',
                ]),
                $parsed['host'] ?? throw new \InvalidArgumentException('A bucket must be set as the host.'), // bucket
                $parsed['path'] ?? '', // prefix
            );
        }

        throw new \LogicException('league/flysystem-async-aws-s3 is required to use the S3 adapter. Install with "composer require league/flysystem-async-aws-s3".');
    }
}
