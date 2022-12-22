<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem;

use Symfony\Component\Stopwatch\Stopwatch;
use Zenstruck\Filesystem\Operation;
use Zenstruck\Filesystem\TraceableFilesystem;
use Zenstruck\Tests\FilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TraceableFilesystemTest extends FilesystemTest
{
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
                Operation::WRITE => [['foo', 'string']],
                Operation::MKDIR => [['bar', null]],
                Operation::CHMOD => [['foo', 'public']],
                Operation::COPY => [['foo', 'file.png']],
                Operation::DELETE => [['foo', null]],
                Operation::MOVE => [['file.png', 'file2.png']],
                Operation::READ => [
                    ['file2.png', 'node'],
                    ['file2.png', 'file'],
                    ['file2.png', 'image'],
                    ['bar', 'dir'],
                    ['file2.png', null],
                ],
            ],
            $filesystem->operations()
        );

        $filesystem->reset();

        $this->assertSame(0, $filesystem->totalReads());
        $this->assertSame(0, $filesystem->totalWrites());
        $this->assertSame(0, $filesystem->totalOperations());
        $this->assertEmpty($filesystem->operations());
    }

    /**
     * @test
     */
    public function can_track_timing(): void
    {
        $filesystem = new TraceableFilesystem(in_memory_filesystem(), $stopwatch = new Stopwatch());

        $filesystem->write('foo.txt', 'content')->delete('foo.txt');

        $this->assertCount(2, $stopwatch->getEvent('filesystem')->getPeriods());
    }

    protected function createFilesystem(): TraceableFilesystem
    {
        return new TraceableFilesystem(in_memory_filesystem());
    }
}
