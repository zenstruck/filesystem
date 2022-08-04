<?php

namespace Zenstruck\Filesystem\Tests;

use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\Feature\FileUrl;
use Zenstruck\Filesystem\Feature\FileUrl\PrefixFileUrlFeature;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AdapterFilesystemTest extends TestCase
{
    /**
     * @test
     */
    public function can_swap_inner_most_adapter_with_a_different_one(): void
    {
        $filesystem = new AdapterFilesystem(FilesystemTest::TEMP_DIR, features: [
            new PrefixFileUrlFeature('http://localhost'),
        ]);
        $filesystem->write('file.txt', 'content');

        $this->assertSame($expectedUrl = 'http://localhost/file.txt', $filesystem->file('file.txt')->url()->toString());
        $this->assertFileExists($realfile = FilesystemTest::TEMP_DIR.'/file.txt');

        $filesystem->delete('file.txt');

        $this->assertFileDoesNotExist($realfile);

        $filesystem->swap(new InMemoryFilesystemAdapter());

        $filesystem->write('file.txt', 'content');

        $this->assertSame($expectedUrl, $filesystem->file('file.txt')->url()->toString());
        $this->assertFileDoesNotExist($realfile);
    }
}
