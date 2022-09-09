<?php

namespace Zenstruck\Filesystem\Adapter;

use AsyncAws\S3\S3Client as AsyncS3Client;
use Aws\S3\S3Client;
use League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\InMemory\StaticInMemoryAdapterRegistry;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
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
            'sftp' => self::createSftpAdapter($dsn),
            's3' => self::createS3Adapter($dsn),
            'in-memory' => self::createInMemoryAdapter($dsn, $name ?? $dsn->fragment() ?? 'default'),
            default => new LocalAdapter($dsn->path()->absolute()),
        };
    }

    private static function createSftpAdapter(Uri $dsn): FilesystemAdapter
    {
        if (!\class_exists(SftpAdapter::class)) {
            throw new \LogicException('league/flysystem-sftp-v3 is required to use the SFTP adapter. Install with "composer require league/flysystem-sftp-v3".');
        }

        return new SftpAdapter(
            new SftpConnectionProvider(
                host: $dsn->host(),
                username: $dsn->user() ?? throw new \InvalidArgumentException('Username is required for SftpAdapter.'),
                password: $dsn->pass(),
                privateKey: $dsn->query()->get('private-key'),
                passphrase: $dsn->query()->get('passphrase'),
                port: $dsn->port() ?? 22,
            ),
            $dsn->path()->absolute(),
        );
    }

    private static function createS3Adapter(Uri $dsn): FilesystemAdapter
    {
        if (\class_exists(AsyncAwsS3Adapter::class)) {
            return new AsyncAwsS3Adapter(
                new AsyncS3Client([
                    'region' => $dsn->fragment() ?? throw new \InvalidArgumentException('A region must be set as the fragment (ie #us-east-1).'),
                    'accessKeyId' => $dsn->user(),
                    'accessKeySecret' => $dsn->pass(),
                ]),
                $dsn->host()->toString(), // bucket
                $dsn->path()->absolute(), // prefix
            );
        }

        if (\class_exists(AwsS3V3Adapter::class)) {
            return new AwsS3V3Adapter( // @phpstan-ignore-line
                new S3Client([ // @phpstan-ignore-line
                    'region' => $dsn->fragment() ?? throw new \InvalidArgumentException('A region must be set as the fragment (ie #us-east-1).'),
                    'credentials' => [
                        'key' => $dsn->user(),
                        'secret' => $dsn->pass(),
                    ],
                ]),
                $dsn->host()->toString(), // bucket
                $dsn->path()->absolute(), // prefix
            );
        }

        throw new \LogicException('league/flysystem-async-aws-s3 is required to use the S3 adapter. Install with "composer require league/flysystem-async-aws-s3".');
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
        if (!\class_exists(InMemoryFilesystemAdapter::class)) {
            throw new \LogicException('league/flysystem-memory is required to use the in-memory adapter. Install with "composer require --dev league/flysystem-memory".');
        }

        if ($dsn->query()->get('static', false)) {
            return new InMemoryFilesystemAdapter();
        }

        if (!\class_exists(StaticInMemoryAdapterRegistry::class)) {
            throw new \LogicException('league/flysystem-memory v3.3+ is required to use the static in-memory adapter.');
        }

        return StaticInMemoryAdapterRegistry::get($name);
    }
}
