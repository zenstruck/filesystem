<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemReader;
use League\Flysystem\FilesystemWriter;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use Zenstruck\Filesystem\Exception\NodeNotFound;
use Zenstruck\Filesystem\Exception\NodeTypeMismatch;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface Filesystem
{
    /**
     * The unique name for this filesystem.
     */
    public function name(): string;

    /**
     * Return a "node" object for the path (could be a file or directory).
     *
     * @throws NodeNotFound           If the path does not exist
     * @throws UnableToCheckExistence
     * @throws FilesystemException
     */
    public function node(string $path): Node;

    /**
     * Return a "file" object for the path.
     *
     * @throws NodeNotFound           If the path does not exist
     * @throws NodeTypeMismatch       If the node at path is not a file
     * @throws UnableToCheckExistence
     * @throws FilesystemException
     */
    public function file(string $path): File;

    /**
     * Return a "directory" object for the path.
     *
     * @throws NodeNotFound           If the path does not exist
     * @throws NodeTypeMismatch       If the node at path is not a directory
     * @throws UnableToCheckExistence
     * @throws FilesystemException
     */
    public function directory(string $path = ''): Directory;

    /**
     * Return an "image" object for the path.
     *
     * @throws NodeNotFound           If the path does not exist
     * @throws NodeTypeMismatch       If the node at path is not an image file
     * @throws UnableToCheckExistence
     * @throws FilesystemException
     */
    public function image(string $path): Image;

    /**
     * Check if the path exists (as a directory or file).
     *
     * @see FilesystemReader::has()
     *
     * @throws UnableToCheckExistence
     * @throws FilesystemException
     */
    public function has(string $path): bool;

    /**
     * Copy a file to another path within the filesystem.
     *
     * @see FilesystemWriter::copy()
     *
     * @throws UnableToCopyFile
     * @throws FilesystemException
     */
    public function copy(string $source, string $destination, array $config = []): File;

    /**
     * Move a file to another path within the filesystem.
     *
     * @see FilesystemWriter::move()
     *
     * @throws UnableToMoveFile
     * @throws FilesystemException
     */
    public function move(string $source, string $destination, array $config = []): File;

    /**
     * Delete a file or directory from the filesystem.
     *
     * This operation does nothing if no file/directory exists
     * for $path.
     *
     * @see FilesystemWriter::delete()
     *
     * @throws UnableToDeleteFile
     * @throws UnableToDeleteDirectory
     * @throws FilesystemException
     */
    public function delete(string $path, array $config = []): self;

    /**
     * Create a directory in the filesystem.
     *
     * @see FilesystemWriter::createDirectory()
     *
     * @param array{
     *     progress?: callable(File=):void
     * } $config
     *
     * @throws \InvalidArgumentException If $content is an \SplFileInfo object but not a directory
     * @throws UnableToCreateDirectory
     * @throws UnableToWriteFile
     * @throws FilesystemException
     */
    public function mkdir(string $path, Directory|\SplFileInfo|null $content = null, array $config = []): Directory;

    /**
     * Change the visibility for a file, and if your filesystem
     * supports it, a directory.
     *
     * @see FilesystemWriter::setVisibility()
     *
     * @throws UnableToSetVisibility
     * @throws FilesystemException
     */
    public function chmod(string $path, string $visibility): Node;

    /**
     * Create/overwrite file in the filesystem.
     *
     * @see FilesystemWriter::write()
     * @see FilesystemWriter::writeStream()
     *
     * @param resource|Stream|File|\SplFileInfo|string $value
     *
     * @throws \InvalidArgumentException If the $value type is not supported
     * @throws UnableToWriteFile
     * @throws FilesystemException
     */
    public function write(string $path, mixed $value, array $config = []): File;
}
