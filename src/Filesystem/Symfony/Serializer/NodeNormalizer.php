<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Symfony\Serializer;

use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\Directory\LazyDirectory;
use Zenstruck\Filesystem\Node\Dsn;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\Image\LazyImage;
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Node\LazyNode;
use Zenstruck\Filesystem\Node\Mapping;
use Zenstruck\Filesystem\Node\Metadata;
use Zenstruck\Filesystem\Node\PathGenerator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NodeNormalizer implements NormalizerInterface, DenormalizerInterface, CacheableSupportsMethodInterface
{
    private const TYPE_MAP = [
        File::class => LazyFile::class,
        Directory::class => LazyDirectory::class,
        Image::class => LazyImage::class,
    ];

    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * @param Node $object
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string
    {
        return Metadata::serialize($object, Mapping::fromArray($context)->metadata);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Node && !$data instanceof PendingFile;
    }

    /**
     * @param string $data
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Node
    {
        if (!\is_string($data) && !\is_array($data)) {
            throw new UnexpectedValueException('Data must be a string or array.');
        }

        $mapping = Mapping::fromArray($context);

        /** @var LazyNode $node */
        $node = new (self::TYPE_MAP[$type])($data);
        $filesystem = $mapping->filesystem();

        if (!$filesystem) { // filesystem defined in context always takes priority
            [$filesystem] = Dsn::normalize(\is_string($data) ? $data : $data[Metadata::DSN] ?? '');
        }

        if ($filesystem) {
            $node->setFilesystem(fn() => $this->container->get('filesystem_locator')->get($filesystem));
        }

        if ($mapping->requiresPathGenerator()) {
            $node->setPath(function() use ($mapping, $node, $context) {
                return $this->container->get(PathGenerator::class)->generate(
                    $mapping->namer(),
                    $node,
                    $context
                );
            });
        }

        return $node;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return isset(self::TYPE_MAP[$type]);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
