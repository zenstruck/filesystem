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
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;
use Zenstruck\Filesystem\Attribute\UploadedFile;
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
        $attribute = UploadedFile::forArgument($argument);

        $files = $this->extractor()->extractFilesFromRequest(
            $request,
            $attribute->path,
            $attribute->multiple,
            $attribute->image,
        );

        if ($files && $attribute->constraints) {
            $errors = $this->validator()->validate(
                $files,
                $attribute->constraints
            );

            if (\count($errors)) {
                throw new HttpException($attribute->errorStatus, (string) $errors);
            }
        }

        return [$files];
    }

    private function extractor(): RequestFilesExtractor
    {
        return $this->locator->get(RequestFilesExtractor::class);
    }

    private function validator(): ValidatorInterface
    {
        return $this->locator->get(ValidatorInterface::class);
    }
}
