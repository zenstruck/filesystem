<?php

namespace Zenstruck\Filesystem\Tests\Util;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Util\ResourceWrapper;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ResourceWrapperTest extends TestCase
{
    /**
     * @test
     */
    public function can_create_in_memory_resource(): void
    {
        $this->assertSame('some data', ResourceWrapper::inMemory()->write('some data')->contents());
        $this->assertSame('different data', \stream_get_contents(ResourceWrapper::inMemory()->write('different data')->rewind()->get()));
    }

    /**
     * @test
     */
    public function can_create_from_string(): void
    {
        $this->assertSame('some data', ResourceWrapper::wrap('some data')->contents());
        $this->assertSame('different data', \stream_get_contents(ResourceWrapper::wrap('different data')->get()));
    }

    /**
     * @test
     */
    public function cannot_write_invalid_data(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ResourceWrapper::inMemory()->write([]);
    }

    /**
     * @test
     */
    public function can_create_temp_file(): void
    {
        $resource = ResourceWrapper::tempFile();
        $path = $resource->uri();

        $resource->write('foo bar');

        $this->assertSame('foo bar', $resource->contents());
        $this->assertFileExists($path);

        $resource->close();

        $this->assertFileDoesNotExist($path);
    }

    /**
     * @test
     */
    public function cannot_wrap_invalid_data(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ResourceWrapper::wrap([]);
    }

    /**
     * @test
     */
    public function can_create_in_output(): void
    {
        \ob_start();
        ResourceWrapper::inOutput()->write('foobar')->close();
        $content = \ob_get_clean();

        $this->assertSame('foobar', $content);
    }

    /**
     * @test
     */
    public function can_get_metadata(): void
    {
        $resource = ResourceWrapper::inMemory();

        $this->assertSame('php://memory', $resource->metadata()['uri']);
        $this->assertSame('php://memory', $resource->metadata('uri'));
    }

    /**
     * @test
     */
    public function cannot_access_invalid_metadata(): void
    {
        $resource = ResourceWrapper::inMemory();

        $this->expectException(\InvalidArgumentException::class);

        $resource->metadata('invalid');
    }
}
