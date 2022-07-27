<?php

namespace Zenstruck\Filesystem\Tests;

use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Adapter\LocalAdapter;
use Zenstruck\Filesystem\Adapter\StaticInMemoryAdapter;
use Zenstruck\Filesystem\Factory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FactoryTest extends TestCase
{
    /**
     * @test
     * @dataProvider dsnProvider
     */
    public function can_create_filesystem_from_dsn($dsn, $name, $expectedAdapter, $expectedName, $expectedFilesystemConfig): void
    {
        $filesystem = (new Factory())->create($dsn, $name);

        $ref = new \ReflectionObject($filesystem);
        $operator = $ref->getProperty('operator');
        $operator->setAccessible(true);
        $ref = new \ReflectionObject($operator = $operator->getValue($filesystem));
        $adapter = $ref->getProperty('adapter');
        $adapter->setAccessible(true);
        $ref = new \ReflectionObject($adapter = $adapter->getValue($operator));
        $ref = $ref->getMethod('adapter');
        $ref->setAccessible(true);
        $adapter = $ref->invoke($adapter);
        $ref = (new \ReflectionObject($operator))->getParentClass();
        $config = $ref->getProperty('config');
        $config->setAccessible(true);
        $config = $config->getValue($operator);
        $ref = (new \ReflectionObject($config))->getProperty('options');
        $ref->setAccessible(true);
        $config = $ref->getValue($config);

        $this->assertSame($expectedAdapter, $adapter::class);
        $this->assertSame($expectedName, $filesystem->name());
        $this->assertSame($expectedFilesystemConfig, $config);
    }

    public static function dsnProvider(): iterable
    {
        yield [FilesystemTest::TEMP_DIR, null, LocalAdapter::class, 'default', []];
        yield [FilesystemTest::TEMP_DIR, 'foo', LocalAdapter::class, 'foo', []];
        yield [FilesystemTest::TEMP_DIR.'#bar', 'foo', LocalAdapter::class, 'foo', []];
        yield [FilesystemTest::TEMP_DIR.'?baz=foo#bar', null, LocalAdapter::class, 'bar', ['baz' => 'foo']];
        yield ['in-memory:', null, InMemoryFilesystemAdapter::class, 'default', []];
        yield ['in-memory:?static', null, StaticInMemoryAdapter::class, 'default', ['static' => '']];
        yield ['in-memory:', 'foo', InMemoryFilesystemAdapter::class, 'foo', []];
        yield ['in-memory:?static#foo', 'bar', StaticInMemoryAdapter::class, 'bar', ['static' => '']];
    }

    /**
     * @test
     */
    public function static_in_memory_filesystem_is_created_with_different_names(): void
    {
        $factory = new Factory();
        $first = $factory->create('in-memory:?static#first');
        $second = $factory->create('in-memory:?static', 'second');

        $first->write('foo', 'bar');

        $this->assertTrue($first->exists('foo'));
        $this->assertFalse($second->exists('foo'));
    }

    /**
     * @test
     */
    public function can_create_for_adapter(): void
    {
        $factory = new Factory();

        $filesystem = $factory->create(new StaticInMemoryAdapter(), name: 'foo');

        $this->assertSame('foo', $filesystem->name());
    }
}
