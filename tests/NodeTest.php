<?php

namespace Zenstruck\Filesystem\Tests;

use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Filesystem\Node\Directory\AdapterDirectory;
use Zenstruck\Filesystem\Node\File\AdapterFile;
use Zenstruck\Filesystem\Node\File\Image\AdapterImage;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NodeTest extends TestCase
{
    /**
     * @test
     */
    public function can_serialize_and_unserialize_nodes(): void
    {
        $filesystem = new MultiFilesystem([
            'first' => new AdapterFilesystem(new InMemoryFilesystemAdapter(), ['name' => 'first']),
            'second' => new AdapterFilesystem(new InMemoryFilesystemAdapter(), ['name' => 'second']),
        ]);

        $filesystem
            ->write('first://sub/file.txt', 'content')
            ->write('second://sub/file.png', 'content')
        ;

        $file = $filesystem->node('first://sub/file.txt')->serialize();
        $image = $filesystem->node('second://sub/file.png')->serialize();
        $dir = $filesystem->node('first://sub')->serialize();

        $this->assertSame('first://sub/file.txt', $file);
        $this->assertSame('second://sub/file.png', $image);
        $this->assertSame('first://sub', $dir);

        $file = AdapterFile::unserialize($file, $filesystem);
        $image = AdapterImage::unserialize($image, $filesystem);
        $dir = AdapterDirectory::unserialize($dir, $filesystem);

        $this->assertSame('sub/file.txt', $file->path());
        $this->assertSame('sub/file.png', $image->path());
        $this->assertSame('sub', $dir->path());
    }
}
