<?php

namespace Zenstruck\Filesystem\Tests\Adapter;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Adapter\StaticInMemoryAdapter;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;
use Zenstruck\Filesystem\Tests\FilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class StaticInMemoryAdapterTest extends FilesystemTest
{
    use InteractsWithFilesystem;

    /**
     * @test
     */
    public function state_is_persisted(): void
    {
        $filesystem1 = $this->createFilesystem();
        $filesystem2 = $this->createFilesystem();
        $filesystem1->write('file.txt', 'contents');

        $this->assertTrue($filesystem1->exists('file.txt'));
        $this->assertTrue($filesystem2->exists('file.txt'));
    }

    /**
     * @test
     */
    public function state_is_kept_by_name(): void
    {
        $filesystem1 = $this->createFilesystem('first');
        $filesystem2 = $this->createFilesystem('second');
        $filesystem1->write('file.txt', 'contents');

        $this->assertTrue($filesystem1->exists('file.txt'));
        $this->assertFalse($filesystem2->exists('file.txt'));
    }

    protected function createFilesystem(?string $name = null): Filesystem
    {
        return new AdapterFilesystem(new StaticInMemoryAdapter($name ?? 'default'));
    }
}
