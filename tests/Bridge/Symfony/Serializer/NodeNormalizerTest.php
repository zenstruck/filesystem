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
    public function can_serialize_and_deserialize_nodes(): void
    {
        /** @var Serializer $serializer */
        $serializer = self::getContainer()->get('serializer');

        $file = $this->filesystem()->write('sub/file.txt', 'content')->last();
        $file = $serializer->serialize($file, 'json');

        $this->assertSame(\json_encode('public://sub/file.txt'), $file);

        $file = $serializer->deserialize($file, File::class, 'json');

        $this->assertInstanceOf(File::class, $file);
        $this->assertSame('sub/file.txt', $file->path());
    }
}
