<?php

namespace Zenstruck\Filesystem\Tests\Bridge\Symfony\Serializer;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Bridge\Symfony\Serializer\NodeNormalizer;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NodeNormalizerTest extends KernelTestCase
{
    use InteractsWithFilesystem;

    /**
     * @test
     * @dataProvider filesystemNodeProvider
     */
    public function can_serialize_and_deserialize_nodes(callable $factory, string $type, string $path): void
    {
        $filesystem = self::getContainer()->get(Filesystem::class);
        $container = new ServiceLocator([
            Filesystem::class => fn() => $filesystem,
        ]);

        $serializer = new Serializer([new NodeNormalizer($container)], [new JsonEncoder()]);

        $node = $factory($filesystem);
        $modified = $node->lastModified();
        $node = $serializer->serialize($node, 'json');

        $this->assertSame(\json_encode('public://'.$path), $node);

        $node = $serializer->deserialize($node, $type, 'json');

        $this->assertInstanceOf($type, $node);
        $this->assertSame($path, $node->path());
        $this->assertEquals($modified, $node->lastModified());
    }

    public function filesystemNodeProvider(): iterable
    {
        yield [
            function(Filesystem $filesystem) {
                return $filesystem->write('sub/file.txt', 'content')->last();
            },
            File::class,
            'sub/file.txt',
        ];

        yield [
            function(Filesystem $filesystem) {
                return $filesystem->write('sub/file.png', 'content')->last()->ensureImage();
            },
            Image::class,
            'sub/file.png',
        ];

        yield [
            function(Filesystem $filesystem) {
                return $filesystem->mkdir('foo/bar')->last();
            },
            Directory::class,
            'foo/bar',
        ];
    }

    /**
     * @test
     * @dataProvider multiFilesystemNodeProvider
     */
    public function can_serialize_and_deserialize_nodes_using_multi_filesystem(callable $factory, string $type, string $path): void
    {
        /** @var Serializer $serializer */
        $serializer = self::getContainer()->get('serializer');

        $node = $factory();
        $modified = $node->lastModified();

        $node = $serializer->serialize($node, 'json');

        $this->assertSame(\json_encode('private://'.$path), $node);

        $node = $serializer->deserialize($node, $type, 'json');

        $this->assertInstanceOf($type, $node);
        $this->assertSame($path, $node->path());
        $this->assertEquals($modified, $node->lastModified());
    }

    public function multiFilesystemNodeProvider(): iterable
    {
        yield [
            function() {
                return $this->filesystem()->write('private://sub/file.txt', 'content')->last();
            },
            File::class,
            'sub/file.txt',
        ];

        yield [
            function() {
                return $this->filesystem()->write('private://sub/file.png', 'content')->last()->ensureImage();
            },
            Image::class,
            'sub/file.png',
        ];

        yield [
            function() {
                return $this->filesystem()->mkdir('private://foo/bar')->last();
            },
            Directory::class,
            'foo/bar',
        ];
    }
}
