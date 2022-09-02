<?php

namespace Zenstruck\Filesystem\Bridge\Intervention;

use Intervention\Image\Image as InterventionImage;
use Intervention\Image\ImageManager;
use Intervention\Image\ImageManagerStatic;
use Zenstruck\Filesystem\Feature\ImageTransformer;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\TempFile;
use Zenstruck\Filesystem\Util\ResourceWrapper;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 *
 * @implements ImageTransformer<InterventionImage>
 */
final class InterventionImageFeature implements ImageTransformer
{
    public function __construct(private ?ImageManager $manager = null)
    {
    }

    public function transform(Image $image, callable $manipulator, array $options): \SplFileInfo
    {
        $resource = ResourceWrapper::wrap($image->read());

        try {
            $interventionImage = $this->manager ? $this->manager->make($resource->get()) : ImageManagerStatic::make($resource->get());
            $interventionImage = $manipulator($interventionImage);

            if (!$interventionImage instanceof InterventionImage) {
                throw new \LogicException('Manipulator callback must return an Intervention\Image object.');
            }

            $interventionImage->save(
                (string) $file = new TempFile(),
                $options['quality'] ?? null,
                $options['format'] ?? $image->guessExtension() ?? throw new \RuntimeException(\sprintf('Unable to guess extension for "%s".', $image->path()))
            );

            return $file->refresh();
        } finally {
            $resource->close();
        }
    }
}
