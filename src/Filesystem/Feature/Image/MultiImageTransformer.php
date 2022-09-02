<?php

namespace Zenstruck\Filesystem\Feature\Image;

use Imagine\Gd\Image as GdImagineImage;
use Imagine\Gmagick\Image as GmagickImagineImage;
use Imagine\Image\ImageInterface as ImagineImage;
use Imagine\Imagick\Image as ImagickImagineImage;
use Intervention\Image\Image as InterventionImage;
use Psr\Container\ContainerInterface;
use Zenstruck\Filesystem\Bridge\Imagine\ImagineImageFeature;
use Zenstruck\Filesystem\Bridge\Intervention\InterventionImageFeature;
use Zenstruck\Filesystem\Feature\ImageTransformer;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @implements ImageTransformer<object>
 */
final class MultiImageTransformer implements ImageTransformer
{
    /** @var array<class-string,ImageTransformer<object>> */
    private static array $defaultTransformers = [];

    /**
     * @param array<class-string,ImageTransformer<object>>|ContainerInterface $transformers
     */
    public function __construct(private array|ContainerInterface $transformers = [])
    {
    }

    public function transform(Image $image, callable $manipulator, array $options): \SplFileInfo
    {
        $ref = new \ReflectionFunction($manipulator instanceof \Closure ? $manipulator : \Closure::fromCallable($manipulator));
        $type = ($ref->getParameters()[0] ?? null)?->getType();

        if (!$type instanceof \ReflectionNamedType) {
            throw new \LogicException('Manipulator callback must have a single typed argument (union/intersection arguments are not allowed).');
        }

        $type = $type->getName();

        if (!\class_exists($type) && !\interface_exists($type)) {
            throw new \LogicException(\sprintf('First parameter type "%s" for manipulator callback is not a valid class/interface.', $type ?: '(none)'));
        }

        return $this->get($type)->transform($image, $manipulator, $options);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return ImageTransformer<T>
     */
    public function get(string $class): ImageTransformer
    {
        if (\is_array($this->transformers) && isset($this->transformers[$class])) {
            return $this->transformers[$class]; // @phpstan-ignore-line
        }

        if ($this->transformers instanceof ContainerInterface && $this->transformers->has($class)) {
            return $this->transformers->get($class);
        }

        return self::defaultTransformer($class);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return ImageTransformer<T>
     */
    private static function defaultTransformer(string $class): ImageTransformer
    {
        return self::$defaultTransformers[$class] ??= match ($class) { // @phpstan-ignore-line
            \GdImage::class => new GdImageFeature(),
            \Imagick::class => new ImagickImageFeature(),
            ImagineImage::class, GdImagineImage::class, ImagickImagineImage::class, GmagickImagineImage::class => ImagineImageFeature::createFor($class),
            InterventionImage::class => new InterventionImageFeature(),
            default => throw new \InvalidArgumentException(\sprintf('No transformer available for "%s".', $class)),
        };
    }
}
