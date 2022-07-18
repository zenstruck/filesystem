<?php

namespace Zenstruck;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemReader;
use League\Flysystem\FilesystemWriter;
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
     */
    public function node(string $path = ''): Node;

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
     * @see FilesystemWriter::copy()
     *
     * @param array<string,mixed> $config
     *
     * @throws FilesystemException
     */
    public function copy(string $source, string $destination, array $config = []): void;

    /**
     * @see FilesystemWriter::move()
     *
     * @param array<string,mixed> $config
     *
     * @throws FilesystemException
     */
    public function move(string $source, string $destination, array $config = []): void;

    /**
     * Delete a file or directory.
     *
     * @see FilesystemWriter::delete()
     * @see FilesystemWriter::deleteDirectory()
     *
     * @param string|Directory<Node>   $path     If {@see Directory}, deletes filtered nodes
     * @param null|callable(Node):void $progress If passed, called before each node is deleted
     *
     * @return int The number of nodes deleted
     *
     * @throws FilesystemException
     */
    public function delete(string|Directory $path = '', ?callable $progress = null): int;

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
     * A callback provided for $values allows for "manipulating" an "existing"
     * file in place. A "real" {@see \SplFileInfo} is passed and must be returned.
     *
     * @see FilesystemWriter::write()
     * @see FilesystemWriter::writeStream()
     *
     * @param resource|string|\SplFileInfo|Directory<Node>|File|callable(\SplFileInfo):\SplFileInfo $value
     * @param array<string,mixed>                                                                   $config
     *
     * @throws NodeNotFound        If a callable is provided for $value and $path does not exist
     * @throws NodeTypeMismatch    If a callable is provided for $value and $path is a directory
     * @throws FilesystemException
     */
    public function write(string $path, mixed $value, array $config = []): void;
}
