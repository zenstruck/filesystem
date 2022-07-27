<?php

namespace Zenstruck\Filesystem\Tests\Adapter;

use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Adapter\AdapterFactory;
use Zenstruck\Filesystem\Adapter\LocalAdapter;
use Zenstruck\Filesystem\Adapter\StaticInMemoryAdapter;
use Zenstruck\Filesystem\AdapterFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AdapterFactoryTest extends TestCase
{
    /**
     * @test
     * @dataProvider dsnProvider
     */
    public function can_create_adapters_from_dsn($dsn, $expectedAdapter): void
    {
        $this->assertSame($expectedAdapter, (new AdapterFactory())->create($dsn)::class);
    }

    public static function dsnProvider(): iterable
    {
        yield ['/tmp', LocalAdapter::class];
        yield ['file:/tmp', LocalAdapter::class];
        yield ['file:///tmp', LocalAdapter::class];
        yield ['in-memory:', InMemoryFilesystemAdapter::class];
        yield ['in-memory:?static', StaticInMemoryAdapter::class];
    }

    /**
     * @test
     */
    public function static_in_memory_adapter_is_created_with_different_names(): void
    {
        $factory = new AdapterFactory();
        $first = new AdapterFilesystem($factory->create('in-memory:?static#first'));
        $second = new AdapterFilesystem($factory->create('in-memory:?static', 'second'));

        $first->write('foo', 'bar');

        $this->assertTrue($first->exists('foo'));
        $this->assertFalse($second->exists('foo'));
    }
}
