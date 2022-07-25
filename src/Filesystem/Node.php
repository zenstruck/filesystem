<?php

namespace Zenstruck\Filesystem;

use League\Flysystem\FilesystemOperator;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Exception\NodeTypeMismatch;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-import-type ImageConfig from Filesystem
 */
interface Node extends \Stringable
{
    public function path(): string;

    /**
     * Returns the file or directory name (with extension if applicable).
     *
     * @example If $path is "foo/bar/baz.txt", returns "baz.txt"
     * @example If $path is "foo/bar/baz", returns "baz"
     */
    public function name(): string;

    /**
     * Returns the "parent" directory path.
     *
     * @example If $path is "foo/bar/baz", returns "foo/bar"
     */
    public function dirname(): string;

    /**
     * @return \DateTimeImmutable In the PHP default timezone
     */
    public function lastModified(): \DateTimeImmutable;

    /**
     * @see FilesystemOperator::visibility()
     */
    public function visibility(): string;

    /**
     * Check if the node still exists.
     */
    public function exists(): bool;

    public function mimeType(): string;

    /**
     * Clear any cached metadata.
     */
    public function refresh(): static;

    /**
     * @throws NodeTypeMismatch If not a file
     */
    public function ensureFile(): File;

    /**
     * @return Directory<Node>
     *
     * @throws NodeTypeMismatch If not a directory
     */
    public function ensureDirectory(): Directory;

    /**
     * @param ImageConfig $config
     *
     * @throws NodeTypeMismatch If not an image file
     */
    public function ensureImage(array $config = []): Image;

    public function isFile(): bool;

    public function isDirectory(): bool;

    /**
     * @param ImageConfig $config
     */
    public function isImage(array $config = []): bool;
}
