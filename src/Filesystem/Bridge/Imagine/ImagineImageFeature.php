<?php

namespace Zenstruck\Filesystem\Bridge\Imagine;

use Imagine\Gd\Image as GdImage;
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Gmagick\Image as GmagickImage;
use Imagine\Gmagick\Imagine as GmagickImagine;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Imagick\Image as ImagickImage;
use Imagine\Imagick\Imagine as ImagickImagine;
use Zenstruck\Filesystem\Feature\ImageTransformer;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Util\ResourceWrapper;
use Zenstruck\Filesystem\Util\TempFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @implements ImageTransformer<ImageInterface>
 */
final class ImagineImageFeature implements ImageTransformer
{
    public function __construct(private ImagineInterface $imagine)
    {
    }

    /**
     * @template T of ImageInterface
     *
     * @param class-string<T> $class
     */
    public static function createFor(string $class): self
    {
        return match ($class) {
            ImageInterface::class, GdImage::class => new self(new GdImagine()),
            ImagickImage::class => new self(new ImagickImagine()),
            GmagickImage::class => new self(new GmagickImagine()),
            default => throw new \InvalidArgumentException('invalid class'),
        };
    }

    public function transform(Image $image, callable $manipulator, array $options): \SplFileInfo
    {
        $resource = ResourceWrapper::wrap($image->read());

        try {
            $imagineImage = $manipulator($this->imagine->read($resource->get()));

            if (!$imagineImage instanceof ImageInterface) {
                throw new \LogicException('Manipulator callback must return an Imagine image object.');
            }

            if (!isset($options['format'])) {
                $options['format'] = $image->guessExtension() ?? throw new \RuntimeException(\sprintf('Unable to guess extension for "%s".', $image->path()));
            }

            $imagineImage->save((string) $file = new TempFile(), $options);

            return $file->refresh();
        } finally {
            $resource->close();
        }
    }
}
