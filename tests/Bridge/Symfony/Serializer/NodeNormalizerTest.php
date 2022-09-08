<?php

namespace Zenstruck\Filesystem\Tests\Bridge\Symfony\Serializer;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Serializer;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NodeNormalizerTest extends KernelTestCase
{
    use InteractsWithFilesystem;

    /**
     * @test
     */
    public function can_serialize_and_deserialize_files(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function can_serialize_and_deserialize_files_using_multi_filesystem(): void
    {
        /** @var Serializer $serializer */
        $serializer = self::getContainer()->get('serializer');

        $file = $this->filesystem()->write('private://sub/file.txt', 'content')->last();

        $this->assertSame('content', $file->contents());

        $file = $serializer->serialize($file, 'json');

        $this->assertSame(\json_encode('private://sub/file.txt'), $file);

        $file = $serializer->deserialize($file, File::class, 'json');

        $this->assertInstanceOf(File::class, $file);
        $this->assertSame('sub/file.txt', $file->path());
        $this->assertSame('content', $file->contents());
    }

    /**
     * @test
     */
    public function can_serialize_and_deserialize_directories(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function can_serialize_and_deserialize_directories_using_multi_filesystem(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function can_serialize_and_deserialize_images(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function can_serialize_and_deserialize_images_using_multi_filesystem(): void
    {
        $this->markTestIncomplete();
    }
}
