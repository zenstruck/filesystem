<?php

namespace Zenstruck\Filesystem\Tests;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\ReadonlyFilesystem;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ReadonlyFilesystemTest extends TestCase
{
    use InteractsWithFilesystem;

    /**
     * @test
     */
    public function can_perform_read_operations(): void
    {
        $filesystem = new ReadonlyFilesystem($this->filesystem()->write('foo/bar.txt', 'content'));

        $this->assertTrue($filesystem->has('foo/bar.txt'));
        $this->assertSame('foo/bar.txt', $filesystem->node('foo/bar.txt')->path());
        $this->assertSame('foo/bar.txt', $filesystem->file('foo/bar.txt')->path());
        $this->assertSame('foo', $filesystem->directory('foo')->path());
    }

    /**
     * @test
     */
    public function cannot_copy(): void
    {
        $this->expectException(\BadMethodCallException::class);

        (new ReadonlyFilesystem($this->filesystem()))->copy('foo', 'bar');
    }

    /**
     * @test
     */
    public function cannot_move(): void
    {
        $this->expectException(\BadMethodCallException::class);

        (new ReadonlyFilesystem($this->filesystem()))->move('foo', 'bar');
    }

    /**
     * @test
     */
    public function cannot_delete(): void
    {
        $this->expectException(\BadMethodCallException::class);

        (new ReadonlyFilesystem($this->filesystem()))->delete('foo');
    }

    /**
     * @test
     */
    public function cannot_chmod(): void
    {
        $this->expectException(\BadMethodCallException::class);

        (new ReadonlyFilesystem($this->filesystem()))->chmod('foo', 'public');
    }

    /**
     * @test
     */
    public function cannot_write(): void
    {
        $this->expectException(\BadMethodCallException::class);

        (new ReadonlyFilesystem($this->filesystem()))->write('foo', 'bar');
    }

    /**
     * @test
     */
    public function cannot_mkdir(): void
    {
        $this->expectException(\BadMethodCallException::class);

        (new ReadonlyFilesystem($this->filesystem()))->mkdir('foo');
    }

    /**
     * @test
     */
    public function cannot_get_last_modified_node(): void
    {
        $this->expectException(\BadMethodCallException::class);

        (new ReadonlyFilesystem($this->filesystem()))->last();
    }
}
