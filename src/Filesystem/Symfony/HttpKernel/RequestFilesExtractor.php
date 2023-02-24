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


use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Zenstruck\Filesystem\Attribute\UploadedFile as UploadedFileAttribute;
use Zenstruck\Filesystem\Node\File\PendingFile;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class RequestFilesExtractor
{
    public function __construct(private PropertyAccessor $propertyAccessor)
    {
    }

    public function extractFilesFromRequest(
        Request $request,
        string $path,
        ?string $expectedType = null
    ): PendingFile|array|null {
        $expectedType ??= PendingFile::class;

        $path = $this->canonizePath($path);

        $files = $this->propertyAccessor->getValue($request->files->all(), $path);

        if (!is_a($expectedType, PendingFile::class, true)) {
            if (!$files) {
                return [];
            }

            if (!\is_array($files)) {
                $files = [$files];
            }

            return \array_map(
                static fn(UploadedFile $file) => new PendingFile($file),
                $files
            );
        }

        if (\is_array($files)) {
            throw new \LogicException(\sprintf('Could not extract files from request for "%s" path: expecting a single file, got %d files.', $path, \count($files)));
        }

        if (!$files) {
            return null;
        }

        return new PendingFile($files);
    }

    public static function supports(ArgumentMetadata $argument): bool
    {
        $attributes = $argument->getAttributes(UploadedFileAttribute::class);

        if (empty($attributes)) {
            $type = $argument->getType();
            return $type && is_a($type, PendingFile::class, true);
        }

        return true;
    }

    /**
     * Convert HTML paths to PropertyAccessor compatible.
     * Examples: "data[file]" -> "[data][file]", "files[]" -> "[files]".
     */
    private function canonizePath(string $path): string
    {
        $path = \preg_replace(
            '/\[]$/',
            '',
            $path
        );
        // Correct arguments passed to preg_replace guarantee string return
        \assert(\is_string($path));

        if ('[' !== $path[0]) {
            $path = \preg_replace(
                '/^([^[]+)/',
                '[$1]',
                $path
            );
            // Correct arguments passed to preg_replace guarantee string return
            \assert(\is_string($path));
        }

        return $path;
    }
}
