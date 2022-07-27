<?php

namespace Zenstruck\Filesystem\Tests;

use Zenstruck\Filesystem\Test\InteractsWithFilesystem;
use Zenstruck\Filesystem\TraceableFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TraceableFilesystemTest extends FilesystemTest
{
    use InteractsWithFilesystem;

    /**
     * @test
     */
    public function can_access_statistics(): void
    {
        $filesystem = $this->createFilesystem()
            ->write('foo', 'bar')
            ->mkdir('bar')
            ->chmod('foo', 'public')
            ->copy('foo', 'file.png')
            ->delete('foo')
            ->move('file.png', 'file2.png')
        ;

        $filesystem->node('file2.png');
        $filesystem->file('file2.png');
        $filesystem->image('file2.png');
        $filesystem->directory('bar');
        $filesystem->has('file2.png');

        $this->assertSame(11, $filesystem->totalOperations());
        $this->assertSame(5, $filesystem->totalReads());
        $this->assertSame(6, $filesystem->totalWrites());
    }

    /**
     * @test
     */
    public function can_access_operations(): void
    {
        $this->markTestIncomplete();
    }

    protected function createFilesystem(): TraceableFilesystem
    {
        return new TraceableFilesystem($this->filesystem());
    }
}
