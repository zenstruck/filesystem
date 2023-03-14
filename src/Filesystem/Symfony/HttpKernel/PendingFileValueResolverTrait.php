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
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Attribute\PendingUploadedFile;
use Zenstruck\Filesystem\Attribute\UploadedFile;
use Zenstruck\Filesystem\Exception\IncorrectFileHttpException;
use Zenstruck\Filesystem\FilesystemRegistry;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Node\Mapping;
use Zenstruck\Filesystem\Node\PathGenerator;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 *
 * @internal
 */
trait PendingFileValueResolverTrait
{
    public function __construct(
        private ServiceProviderInterface $locator
    ) {
    }

    /**
     * @return iterable<PendingFile|array|null>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $attribute = PendingUploadedFile::forArgument($argument);

        $files = $this->extractor()->extractFilesFromRequest(
            $request,
            (string) $attribute->path,
            'array' === $argument->getType(),
            (bool) $attribute->image,
        );

        if (!$files) {
            return [$files];
        }

        if ($attribute->constraints) {
            $errors = $this->validator()->validate(
                $files,
                $attribute->constraints
            );

            if (\count($errors)) {
                \assert($errors instanceof ConstraintViolationList);

                throw new IncorrectFileHttpException($attribute->errorStatus, (string) $errors);
            }
        }

        if ($attribute instanceof UploadedFile) {
            if (is_array($files)) {
                $files = array_map(
                    fn (PendingFile $file) => $this->saveFile($attribute, $file),
                    $files
                );
            } else {
                $files = $this->saveFile($attribute, $files);
            }
        }

        return [$files];
    }

    private function saveFile(UploadedFile $uploadedFile, PendingFile $file): File
    {
        $path = $this->generatePath($uploadedFile, $file);
        $file = $this->filesystem($uploadedFile->filesystem)
            ->write($path, $file);

        if ($uploadedFile->image) {
            return $file->ensureImage();
        }

        return $file;
    }

    private function extractor(): RequestFilesExtractor
    {
        return $this->locator->get(RequestFilesExtractor::class);
    }

    private function filesystem(string $filesystem): Filesystem
    {
        return $this->locator->get(FilesystemRegistry::class)->get($filesystem);
    }

    private function generatePath(UploadedFile $uploadedFile, Node $node): string
    {
        return $this->locator->get(PathGenerator::class)->generate(
            $uploadedFile->namer,
            $node
        );
    }

    private function validator(): ValidatorInterface
    {
        return $this->locator->get(ValidatorInterface::class);
    }
}
