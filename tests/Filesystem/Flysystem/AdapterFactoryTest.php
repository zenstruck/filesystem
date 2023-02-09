<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Flysystem;

use League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter;
use League\Flysystem\Config;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\InMemory\StaticInMemoryAdapterRegistry;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\ReadOnly\ReadOnlyFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Flysystem\AdapterFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AdapterFactoryTest extends TestCase
{
    /**
     * @after
     */
    public function cleanup(): void
    {
        StaticInMemoryAdapterRegistry::deleteAllFilesystems();
    }

    /**
     * @test
     * @dataProvider dsnProvider
     */
    public function can_create_adapters_from_dsn($dsn, $expectedAdapter): void
    {
        $this->assertSame($expectedAdapter, AdapterFactory::createAdapter($dsn)::class);
    }

    public static function dsnProvider(): iterable
    {
        yield ['/tmp', LocalFilesystemAdapter::class];
        yield ['file:/tmp', LocalFilesystemAdapter::class];
        yield ['file:///tmp', LocalFilesystemAdapter::class];
        yield ['in-memory:', InMemoryFilesystemAdapter::class];
        yield ['in-memory:foo', InMemoryFilesystemAdapter::class];
        yield ['flysystem+ftp://foo:bar@example.com/path', FtpAdapter::class];
        yield ['flysystem+ftps://foo:bar@example.com/path', FtpAdapter::class];
        yield ['flysystem+sftp://foo:bar@example.com/path', SftpAdapter::class];
        yield ['flysystem+s3://accessKeyId:accessKeySecret@bucket/prefix#us-east-1', AsyncAwsS3Adapter::class];
        yield ['flysystem+s3://accessKeyId:accessKeySecret@bucket/prefix?region=us-east-1', AsyncAwsS3Adapter::class];
        yield ['readonly:flysystem+ftp://foo:bar@example.com/path', ReadOnlyFilesystemAdapter::class];
    }

    /**
     * @test
     */
    public function static_in_memory_adapter_is_created_with_different_names(): void
    {
        $first = AdapterFactory::createAdapter('in-memory:first');
        $second = AdapterFactory::createAdapter('in-memory:second');

        $first->write('foo', 'bar', new Config());

        $this->assertTrue($first->fileExists('foo'));
        $this->assertFalse($second->fileExists('foo'));
    }
}
