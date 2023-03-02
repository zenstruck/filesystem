<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Node\File;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\TempFile;
use Zenstruck\Tests\Filesystem\Node\FileTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class PendingFileTest extends TestCase
{
    use FileTests;

    /**
     * @test
     */
    public function path_info(): void
    {
        $file = $this->createPendingFile($tempFile = TempFile::for('content', 'png'), \pathinfo($tempFile, \PATHINFO_BASENAME));

        $this->assertSame('png', $file->path()->extension());
        $this->assertSame(\pathinfo($tempFile, \PATHINFO_FILENAME), $file->path()->basename());
        $this->assertSame($tempFile->getFilename(), $file->path()->name());
    }

    /**
     * @test
     */
    public function metadata_cached(): void
    {
        $this->markTestSkipped('Caching not supported.');
    }

    /**
     * @test
     */
    public function file_urls(): void
    {
        $this->markTestSkipped('File urls not supported.');
    }

    protected function createPendingFile(\SplFileInfo $file, string $filename): PendingFile
    {
        return new PendingFile($file);
    }

    protected function pendingFileClass(): string
    {
        return PendingFile::class;
    }

    protected function createFile(\SplFileInfo $file, string $path): File
    {
        $file = TempFile::for($file, \pathinfo($path, \PATHINFO_EXTENSION) ?: null);

        return $this->createPendingFile($file, \pathinfo($path, \PATHINFO_BASENAME));
    }

    /**
     * @param PendingFile $file
     */
    protected function modifyFile(File $file, \SplFileInfo $new): void
    {
        \file_put_contents($file, \file_get_contents($new));
    }
}
