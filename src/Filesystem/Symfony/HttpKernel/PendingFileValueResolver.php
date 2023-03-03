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
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Contracts\Service\ServiceProviderInterface;
use Zenstruck\Filesystem\Attribute\UploadedFile;
use Zenstruck\Filesystem\Node\File\Image\PendingImage;
use Zenstruck\Filesystem\Node\File\PendingFile;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 *
 * @internal
 */
if (\interface_exists(ValueResolverInterface::class)) {
    class PendingFileValueResolver implements ValueResolverInterface
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

            if (!RequestFilesExtractor::supports($argument)) {
                return [];
            }

            /** @var UploadedFile|null $attribute */
            $attribute = $attributes[0] ?? null;

            $path = $attribute?->path
                ?? $argument->getName();

            return [
                $this->extractor()->extractFilesFromRequest(
                    $request,
                    $path,
                    !\is_a(
                        $argument->getType() ?? PendingFile::class,
                        PendingFile::class,
                        true
                    ),
                    $attribute?->image || PendingImage::class === $argument->getType()
                ),
            ];
        }

        private function extractor(): RequestFilesExtractor
        {
            return $this->locator->get(RequestFilesExtractor::class);
        }
    }
} else {
    class PendingFileValueResolver implements ArgumentValueResolverInterface
    {
        public function __construct(
            /** @var ServiceProviderInterface<RequestFilesExtractor> $locator */
            private ServiceProviderInterface $locator
        ) {
        }

        public function supports(Request $request, ArgumentMetadata $argument): bool
        {
            return RequestFilesExtractor::supports($argument);
        }

        /**
         * @return iterable<PendingFile|array|null>
         */
        public function resolve(Request $request, ArgumentMetadata $argument): iterable
        {
            $attributes = $argument->getAttributes(UploadedFile::class);
            \assert(!empty($attributes));

            /** @var UploadedFile|null $attribute */
            $attribute = $attributes[0] ?? null;

            $path = $attribute?->path
                ?? $argument->getName();

            return [
                $this->extractor()->extractFilesFromRequest(
                    $request,
                    $path,
                    !\is_a(
                        $argument->getType() ?? PendingFile::class,
                        PendingFile::class,
                        true
                    ),
                    $attribute?->image || PendingImage::class === $argument->getType()
                ),
            ];
        }

        private function extractor(): RequestFilesExtractor
        {
            return $this->locator->get(RequestFilesExtractor::class);
        }
    }
}
