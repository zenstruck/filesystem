<?php

namespace Zenstruck\Filesystem\Tests;

use Zenstruck\Filesystem\Test\InteractsWithFilesystem;
use Zenstruck\Filesystem\Test\Node\TestDirectory;
use Zenstruck\Filesystem\Test\Node\TestFile;
use Zenstruck\Filesystem\Test\Node\TestImage;
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
    public function can_access_statistics_and_operations(): void
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

        $this->assertSame(
            [
                'write' => [['foo', 'string']],
                'mkdir' => [['bar', null]],
                'chmod' => [['foo', 'public']],
                'copy' => [['foo', 'file.png']],
                'delete' => [['foo', null]],
                'move' => [['file.png', 'file2.png']],
                'read' => [
                    ['file2.png', TestFile::class],
                    ['file2.png', TestFile::class],
                    ['file2.png', TestImage::class],
                    ['bar', TestDirectory::class],
                    ['file2.png', null],
                ],
            ],
            $filesystem->operations()
        );
    }

    protected function createFilesystem(): TraceableFilesystem
    {
        return new TraceableFilesystem($this->filesystem());
    }
}
