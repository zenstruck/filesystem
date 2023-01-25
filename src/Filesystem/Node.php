<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem;

use Zenstruck\Filesystem\Exception\NodeTypeMismatch;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\Dsn;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\Path;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface Node
{
    public function path(): Path;

    /**
     * Returns standardized node identification, containing library name and node path.
     *
     * @example If node comes from "public" filesystem with "foo/bar.txt" path, returns "public://foo/bar.txt"
     */
    public function dsn(): Dsn;

    /**
     * Returns the "parent" directory.
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
     * @throws NodeTypeMismatch if this node is not a directory
     */
    public function ensureDirectory(): Directory;

    /**
     * @throws NodeTypeMismatch if this node is not an image file
     */
    public function ensureImage(): Image;
}
