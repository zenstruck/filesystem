<?php

namespace Zenstruck\Filesystem\Bridge\Symfony\Serializer;

use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Zenstruck\Filesystem\FilesystemRegistry;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\Directory\LazyDirectory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\Image\LazyImage;
use Zenstruck\Filesystem\Node\File\LazyFile;

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
     * @param Node    $object
     * @param mixed[] $context
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): string
    {
        return $object->context();
    }

    /**
     * @param mixed[] $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Node;
    }

    /**
     * @param string                                   $data
     * @param class-string<File|Image|Directory<Node>> $type
     * @param mixed[]                                  $context
     *
     * @return File|Image|Directory<Node>
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): File|Image|Directory
    {
        if (!\is_string($data)) {
            throw NotNormalizableValueException::createForUnexpectedDataType('The data must be a string.', $data, [Type::BUILTIN_TYPE_STRING], $context['deserialization_path'] ?? null, true);
        }

        $parts = \explode('://', $data, 2);
        $name = 2 === \count($parts) ? $parts[0] : null;
        $path = $parts[1] ?? $parts[0];

        return new (self::TYPE_MAP[$type])($path, $this->container->get(FilesystemRegistry::class)->get($name));
    }

    /**
     * @param mixed[] $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return isset(self::TYPE_MAP[$type]);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
