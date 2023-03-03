<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Symfony\HttpKernel;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Contracts\Service\ServiceProviderInterface;
use Zenstruck\Filesystem\Attribute\UploadedFile;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\PendingFile;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 *
 * @internal
 */
trait PendingFileValueResolverTrait
{
    public function __construct(
        /** @var ServiceProviderInterface<RequestFilesExtractor> $locator */
        private ServiceProviderInterface $locator
    ) {
    }

    /**
     * @return iterable<PendingFile|array|null>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $attributes = $argument->getAttributes(UploadedFile::class);

        /** @var UploadedFile|null $attribute */
        $attribute = $attributes[0] ?? null;

        $path = $attribute?->path
            ?? $argument->getName();

        return [
            $this->extractor()->extractFilesFromRequest(
                $request,
                $path,
                !\is_a(
                    $argument->getType() ?? File::class,
                    File::class,
                    true
                ),
                $attribute?->image
                || \is_a(
                    $argument->getType() ?? File::class,
                    Image::class,
                    true
                ),
            ),
        ];
    }

    private function extractor(): RequestFilesExtractor
    {
        return $this->locator->get(RequestFilesExtractor::class);
    }
}
