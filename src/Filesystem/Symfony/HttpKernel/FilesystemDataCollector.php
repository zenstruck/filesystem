<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Symfony\HttpKernel;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Zenstruck\Filesystem\TraceableFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class FilesystemDataCollector extends DataCollector
{
    /** @var TraceableFilesystem[] */
    private array $filesystems = [];

    public function addFilesystem(TraceableFilesystem $filesystem): void
    {
        $this->filesystems[] = $filesystem;
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->data = [
            'total_duration' => 0,
            'total_operations' => 0,
            'total_reads' => 0,
            'total_writes' => 0,
            'filesystems' => [],
        ];

        foreach ($this->filesystems as $filesystem) {
            $this->data['total_duration'] += $filesystem->totalDuration();
            $this->data['total_operations'] += $filesystem->totalOperations();
            $this->data['total_reads'] += $filesystem->totalReads();
            $this->data['total_writes'] += $filesystem->totalWrites();
            $this->data['filesystems'][$filesystem->name()] = [
                'total_duration' => $filesystem->totalDuration(),
                'total_operations' => $filesystem->totalOperations(),
                'total_reads' => $filesystem->totalReads(),
                'total_writes' => $filesystem->totalWrites(),
                'operations' => $filesystem->operations(),
            ];
        }
    }

    public function getData(): array // @phpstan-ignore-line
    {
        return $this->data; // @phpstan-ignore-line
    }

    public function getName(): string
    {
        return 'filesystem';
    }

    public function reset(): void
    {
        $this->data = [];

        foreach ($this->filesystems as $filesystem) {
            $filesystem->reset();
        }
    }
}
