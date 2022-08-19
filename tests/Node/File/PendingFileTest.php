<?php

namespace Zenstruck\Filesystem\Tests\Node\File;

use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Filesystem\Node\File\PendingFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PendingFileTest extends TestCase
{
    /**
     * @test
     */
    public function cannot_be_serialized(): void
    {
        $file = new PendingFile(__FILE__);

        $this->expectException(\BadMethodCallException::class);

        $file->serialize();
    }

    /**
     * @test
     */
    public function cannot_be_unserialized(): void
    {
        $filesystem = new MultiFilesystem([
            new AdapterFilesystem(new InMemoryFilesystemAdapter()),
        ]);

        $this->expectException(\BadMethodCallException::class);

        PendingFile::unserialize('some string', $filesystem);
    }
}
