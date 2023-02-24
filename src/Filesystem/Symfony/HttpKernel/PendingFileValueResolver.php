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

            if (
                empty($attributes)
                && PendingFile::class !== $argument->getType()
            ) {
                return [];
            }

            $path = $attributes[0]?->path
                ?? $argument->getName();

            return [
                $this->extractor()->extractFilesFromRequest(
                    $request,
                    $path,
                    PendingFile::class !== $argument->getType()
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
            return PendingFile::class === $argument->getType()
                || !empty($argument->getAttributes(UploadedFile::class));
        }

        /**
         * @return iterable<PendingFile|array|null>
         */
        public function resolve(Request $request, ArgumentMetadata $argument): iterable
        {
            $attributes = $argument->getAttributes(UploadedFile::class);
            \assert(!empty($attributes));

            $path = $attributes[0]?->path
                ?? $argument->getName();

            return [
                $this->extractor()->extractFilesFromRequest(
                    $request,
                    $path,
                    PendingFile::class !== $argument->getType()
                ),
            ];
        }

        private function extractor(): RequestFilesExtractor
        {
            return $this->locator->get(RequestFilesExtractor::class);
        }
    }
}
