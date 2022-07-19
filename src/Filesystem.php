<?php

namespace Zenstruck;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemReader;
use League\Flysystem\FilesystemWriter;
use Zenstruck\Filesystem\Flysystem\Exception\NodeExists;
use Zenstruck\Filesystem\Flysystem\Exception\NodeNotFound;
use Zenstruck\Filesystem\Flysystem\Exception\NodeTypeMismatch;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface Filesystem
{
    /**
     * @throws NodeNotFound        If node at path is not found
     * @throws FilesystemException {@see FilesystemReader::has()}
     *
     * @return File|Directory<Node>
     */
    public function node(string $path = ''): File|Directory;

    /**
     * @throws NodeNotFound        If node at path is not found
     * @throws NodeTypeMismatch    If the node at path is a directory
     * @throws FilesystemException {@see FilesystemReader::has()}
     */
    public function file(string $path): File;

    /**
     * @return Directory<Node>
     *
     * @throws NodeNotFound        If node at path is not found
     * @throws NodeTypeMismatch    If the node at path is a file
     * @throws FilesystemException {@see FilesystemReader::has()}
     */
    public function directory(string $path = ''): Directory;

    /**
     * @see FilesystemReader::has()
     *
     * @throws FilesystemException
     */
    public function exists(string $path = ''): bool;

    /**
     * Copy a file.
     *
     * Overrides $destination if exists, pass $config "fail_if_exists" => true to instead
     * throw a {@see NodeExists} exception.
     *
     * @see FilesystemWriter::copy()
     *
     * @param array<string,mixed>|array{'fail_if_exists':bool} $config
     *
     * @throws NodeExists          If the $destination exists and "fail_if_exists" => true
     * @throws FilesystemException
     */
    public function copy(string $source, string $destination, array $config = []): void;

    /**
     * Move a file.
     *
     * Overrides $destination if exists, pass $config "fail_if_exists" => true to instead
     * throw a {@see NodeExists} exception.
     *
     * @see FilesystemWriter::move()
     *
     * @param array<string,mixed>|array{'fail_if_exists':bool} $config
     *
     * @throws NodeExists          If the $destination exists and "fail_if_exists" => true
     * @throws FilesystemException
     */
    public function move(string $source, string $destination, array $config = []): void;

    /**
     * Delete a file or directory.
     *
     * You can pass a "progress" callback to $config that is called before each
     * node is deleted with said node as the argument (useful when deleting a
     * filtered {@see Directory}).
     *
     * EXAMPLE:
     *
     * ```php
     * $filesystem->delete($directory, [
     *      'progress' => fn(Node $node) => ...do something with $node
     * ]);
     * ```
     *
     * @see FilesystemWriter::delete()
     * @see FilesystemWriter::deleteDirectory()
     *
     * @param string|Directory<Node>                                    $path   If {@see Directory}, deletes filtered nodes
     * @param array<string,mixed>|array{'progress':callable(Node):void} $config If passed, called before each node is deleted
     *
     * @return int The number of nodes deleted
     *
     * @throws FilesystemException
     */
    public function delete(string|Directory $path = '', array $config = []): int;

    /**
     * @see FilesystemWriter::createDirectory()
     *
     * @param array<string,mixed> $config
     *
     * @throws FilesystemException
     */
    public function mkdir(string $path = '', array $config = []): void;

    /**
     * @see FilesystemWriter::setVisibility()
     *
     * @throws FilesystemException
     */
    public function chmod(string $path, string $visibility): void;

    /**
     * Write $value to the filesystem.
     *
     * Overrides $path if exists, pass $config "fail_if_exists" => true to instead
     * throw a {@see NodeExists} exception.
     *
     * A callback provided for $values allows for "manipulating" an "existing"
     * file in place. A "real" {@see \SplFileInfo} is passed and must be returned.
     *
     * EXAMPLE - Manipulate an image:
     *
     * ```php
     * $filesystem->write('path/to/image.jpg', function(\SplFileInfo $file) {
     *      $this->imageManipulator()->load($file)->sharpen();
     *
     *      return $file;
     * });
     * ```
     *
     * You can pass a "progress" callback to $config that is called before each
     * file is written with said file as an argument (useful when writing
     * {@see Directory} or local {@see \SplFileInfo} directories).
     *
     * EXAMPLE - Track files written:
     *
     * ```php
     * $filesWritten = [];
     *
     * $filesystem->write('some/path', $directory, [
     *      'progress' => function(File $file) use (&$filesWritten) {
     *          $filesWritten[] = $file;
     *      }
     * ]);
     *
     * \count($filesWritten);
     * ```
     *
     * @see FilesystemWriter::write()
     * @see FilesystemWriter::writeStream()
     *
     * @param resource|string|\SplFileInfo|Directory<Node>|File|callable(\SplFileInfo):\SplFileInfo $value
     * @param array<string,mixed>|array{'progress':callable(File):void,'fail_if_exists':bool}       $config
     *
     * @return File|Directory<Node>
     *
     * @throws NodeNotFound        If a callable is provided for $value and $path does not exist
     * @throws NodeTypeMismatch    If a callable is provided for $value and $path is a directory
     * @throws NodeExists          If the $path exists and "fail_if_exists" => true
     * @throws FilesystemException
     */
    public function write(string $path, mixed $value, array $config = []): File|Directory;
}
