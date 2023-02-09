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
        if (false === $parsed = \parse_url($dsn)) {
            throw new \InvalidArgumentException(\sprintf('Could not parse "%s".', $dsn));
        }

        $scheme = $parsed['scheme'] ?? null;

        if ('file' === $scheme) {
            $scheme = null;
            $dsn = \mb_substr($dsn, \str_starts_with($dsn, 'file://') ? 7 : 5);
        }

        if (!$scheme) {
            return new LocalFilesystemAdapter($dsn);
        }

        $query = [];

        \parse_str($parsed['query'] ??= '', $query);

        $parsed['query'] = $query;

        return match ($scheme) {
            'flysystem+ftp', 'flysystem+ftps' => self::createFtpAdapter($parsed),
            'flysystem+sftp' => self::createSftpAdapter($parsed),
            'flysystem+s3' => self::createS3Adapter($parsed),
            'in-memory' => self::createInMemoryAdapter($parsed),
            default => throw new \InvalidArgumentException(\sprintf('Could not parse DSN "%s".', $dsn)),
        };
    }

    private static function createInMemoryAdapter(array $parsed): InMemoryFilesystemAdapter
    {
        if ($name = $parsed['path'] ?? null) {
            return StaticInMemoryAdapterRegistry::get($name);
        }

        return new InMemoryFilesystemAdapter();
    }

    private static function createSftpAdapter(array $parsed): FilesystemAdapter
    {
        if (!\class_exists(SftpAdapter::class)) {
            throw new \LogicException('league/flysystem-sftp-v3 is required to use the SFTP adapter. Install with "composer require league/flysystem-sftp-v3".');
        }

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

    private static function createFtpAdapter(array $parsed): FilesystemAdapter
    {
        if (!\class_exists(FtpAdapter::class)) {
            throw new \LogicException('league/flysystem-ftp is required to use the FTP adapter. Install with "composer require league/flysystem-ftp".');
        }

        return new FtpAdapter(FtpConnectionOptions::fromArray(\array_filter([
            'host' => $parsed['host'] ?? null,
            'root' => $parsed['path'] ?? null,
            'username' => $parsed['user'] ?? null,
            'password' => $parsed['pass'] ?? null,
            'port' => $parsed['port'] ?? 21,
            'ssl' => (bool) ($parsed['query']['ssl'] ?? 'flysystem+ftps' === ($parsed['scheme'] ?? null)),
        ])));
    }

    private static function createS3Adapter(array $parsed): FilesystemAdapter
    {
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
                ]),
                $parsed['host'] ?? throw new \InvalidArgumentException('A bucket must be set as the host.'), // bucket
                $parsed['path'] ?? '', // prefix
            );
        }

        throw new \LogicException('league/flysystem-async-aws-s3 is required to use the S3 adapter. Install with "composer require league/flysystem-async-aws-s3".');
    }
}
