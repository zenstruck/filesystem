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
     * @see FilesystemWriter::delete()
     * @see FilesystemWriter::deleteDirectory()
     *
     * @throws FilesystemException
     */
    public function delete(string $path = ''): void;

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
     * @see FilesystemWriter::write()
     * @see FilesystemWriter::writeStream()
     *
     * @param resource|string|\SplFileInfo|Directory<Node>|File $value
     * @param array<string,mixed>                               $config
     *
     * @throws FilesystemException
     */
    public function write(string $path, mixed $value, array $config = []): void;
}
