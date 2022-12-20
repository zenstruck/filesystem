<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Symfony\Serializer;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Filesystem\Symfony\Serializer\NodeNormalizer;
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
    public function can_normalize_and_denormalize(): void
    {
        $file = $this->filesystem()->write('some/file.txt', 'content')->last();

        $serialized = $this->serializer()->serialize($file, 'json');

        $this->assertSame(\json_encode('public://some/file.txt'), $serialized);

        $deserialized = $this->serializer()->deserialize($serialized, File::class, 'json');

        $this->assertInstanceOf(LazyFile::class, $deserialized);
        $this->assertSame('some/file.txt', $deserialized->path()->toString());
        $this->assertSame('content', $deserialized->contents());
    }

    /**
     * @test
     */
    public function can_normalize_and_denormalize_with_filesystem_context(): void
    {
        $file = $this->filesystem()->write('some/file.txt', 'content1')->last();

        $serialized = $this->serializer()->serialize($file, 'json', [NodeNormalizer::FILESYSTEM_KEY => 'public']);

        $this->assertSame(\json_encode('some/file.txt'), $serialized);

        $deserialized = $this->serializer()->deserialize($serialized, File::class, 'json', [NodeNormalizer::FILESYSTEM_KEY => 'public']);

        $this->assertInstanceOf(LazyFile::class, $deserialized);
        $this->assertSame('some/file.txt', $deserialized->path()->toString());
        $this->assertSame('content1', $deserialized->contents());
    }

    private function serializer(): SerializerInterface
    {
        return self::getContainer()->get('serializer');
    }
}
