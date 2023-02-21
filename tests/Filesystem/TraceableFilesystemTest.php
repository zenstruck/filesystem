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
        $filesystem = $this->createFilesystem();
        $filesystem->write('foo', 'bar');
        $filesystem->mkdir('bar');
        $filesystem->chmod('foo', 'public');
        $filesystem->copy('foo', 'file.png');
        $filesystem->delete('foo');
        $filesystem->move('file.png', 'file2.png');

        $filesystem->node('file2.png');
        $filesystem->file('file2.png');
        $filesystem->image('file2.png');
        $filesystem->directory('bar');
        $filesystem->has('file2.png');

        $this->assertSame(11, $filesystem->totalOperations());
        $this->assertSame(5, $filesystem->totalReads());
        $this->assertSame(6, $filesystem->totalWrites());

        $operations = $filesystem->operations();

        // remove durations
        foreach ($operations as $i => $set) {
            foreach ($set as $j => $data) {
                unset($operations[$i][$j][2]);
            }
        }

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
            $operations
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
    public function can_use_stopwatch(): void
    {
        $filesystem = new TraceableFilesystem(in_memory_filesystem(), $stopwatch = new Stopwatch());

        $filesystem->write('foo.txt', 'content');
        $filesystem->delete('foo.txt');

        $this->assertCount(2, $stopwatch->getEvent('filesystem.default')->getPeriods());
    }

    /**
     * @test
     */
    public function can_track_duration(): void
    {
        $filesystem = $this->createFilesystem();

        $filesystem->write('file.txt', 'content');
        $filesystem->has('file.txt');
        $filesystem->delete('file.txt');

        $totalDuration = 0;

        $this->assertCount(3, $filesystem->operations());

        foreach ($filesystem->operations() as $operation) {
            foreach ($operation as [$path, $context, $duration]) {
                $totalDuration += $duration;
            }
        }

        $this->assertSame($totalDuration, $filesystem->totalDuration());
    }

    protected function createFilesystem(): TraceableFilesystem
    {
        return new TraceableFilesystem(in_memory_filesystem());
    }
}
