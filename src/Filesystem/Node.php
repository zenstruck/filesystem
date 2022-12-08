<?php

namespace Zenstruck\Filesystem;

use Zenstruck\Filesystem\Exception\NodeTypeMismatch;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface Node
{
    public function path(): string;

    /**
     * Returns the file or directory name (with extension if applicable).
     *
     * @example If path is "foo/bar/baz.txt", returns "baz.txt"
     * @example If path is "foo/bar/baz", returns "baz"
     */
    public function name(): string;

    /**
     * Returns the "parent" directory.
     *
     * @return ?Directory<Node>
     */
    public function directory(): ?Directory;

    /**
     * @return \DateTimeImmutable In the PHP default timezone
     */
    public function lastModified(): \DateTimeImmutable;

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
     * @throws NodeTypeMismatch if this node is not a file
     */
    public function ensureFile(): File;

    /**
     * @return Directory<Node>
     *
     * @throws NodeTypeMismatch if this node is not a directory
     */
    public function ensureDirectory(): Directory;

    /**
     * @throws NodeTypeMismatch if this node is not an image file
     */
    public function ensureImage(): Image;
}
