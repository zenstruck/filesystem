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

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemReader;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToRetrieveMetadata;
use Zenstruck\Filesystem\Exception\NodeNotFound;
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
     * @see FilesystemReader::lastModified()
     *
     * @return \DateTimeImmutable In the PHP default timezone
     *
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function lastModified(): \DateTimeImmutable;

    /**
     * @see FilesystemReader::visibility()
     *
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function visibility(): string;

    /**
     * Check if the node still exists.
     *
     * @see FilesystemReader::has()
     *
     * @throws UnableToCheckExistence
     * @throws FilesystemException
     */
    public function exists(): bool;

    /**
     * Clear any cached metadata.
     */
    public function refresh(): static;

    /**
     * @throws NodeNotFound
     * @throws UnableToCheckExistence
     * @throws FilesystemException
     */
    public function ensureExists(): static;

    /**
     * @throws NodeTypeMismatch    if this node is not a file
     * @throws FilesystemException
     */
    public function ensureFile(): File;

    /**
     * @throws NodeTypeMismatch    if this node is not a directory
     * @throws FilesystemException
     */
    public function ensureDirectory(): Directory;

    /**
     * @throws NodeTypeMismatch    if this node is not an image file
     * @throws FilesystemException
     */
    public function ensureImage(): Image;
}
