<?php

namespace Zenstruck;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemReader;
use League\Flysystem\FilesystemWriter;
use Zenstruck\Filesystem\Exception\NodeExists;
use Zenstruck\Filesystem\Exception\NodeNotFound;
use Zenstruck\Filesystem\Exception\NodeTypeMismatch;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-type CopyConfig = array{
 *     fail_if_exists: bool,
 *     progress: callable(File):void,
 * }
 * @phpstan-type MoveConfig = array{
 *     fail_if_exists: bool,
 *     progress: callable(File):void,
 * }
 * @phpstan-type DeleteConfig = array{
 *     progress: callable(Node):void,
 * }
 * @phpstan-type WriteConfig = array{
 *     progress: callable(File):void,
 *     fail_if_exists: bool,
 * }
 * @phpstan-type ImageConfig = array{
 *     check_mime?:bool,
 * }
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
     * Returns an image file type. By default, only ensures a file's
     * extension is a valid image extension. If you'd like to check
     * the file's mime-type, pass the $config "check_mime" => true.
     *
     * @param ImageConfig $config
     *
     * @throws NodeNotFound        If node at path is not found
     * @throws NodeTypeMismatch    If the node at path is not an image file
     * @throws FilesystemException {@see FilesystemReader::has()}
     */
    public function image(string $path, array $config = []): Image;

    /**
     * @see FilesystemReader::has()
     *
     * @throws FilesystemException
     */
    public function exists(string $path = ''): bool;

    /**
     * Copy a file or directory.
     *
     * Overrides $destination if exists, pass $config "fail_if_exists" => true to instead
     * throw a {@see NodeExists} exception.
     *
     * If copying a directory, you can pass a "progress" callback to $config
     * that is called before each file is copied with said file as an argument.
     *
     * EXAMPLE - Track files copied:
     *
     * ```php
     * $filesCopied = [];
     *
     * $filesystem->copy('source/dir', 'dest/dir', [
     *      'progress' => function(File $file) use (&$filesCopied) {
     *          $filesCopied[] = $file;
     *      }
     * ]);
     *
     * \count($filesCopied);
     *
     * @see FilesystemWriter::copy()
     *
     * @param array<string,mixed>|CopyConfig $config
     *
     * @throws NodeExists          If the $destination exists and "fail_if_exists" => true
     * @throws FilesystemException
     */
    public function copy(string $source, string $destination, array $config = []): static;

    /**
     * Move a file or directory.
     *
     * Overrides $destination if exists, pass $config "fail_if_exists" => true to instead
     * throw a {@see NodeExists} exception.
     *
     * If moving a directory, you can pass a "progress" callback to $config
     * that is called before each file is moved with said file as an argument.
     *
     * EXAMPLE - Track files moved:
     *
     * ```php
     * $filesMoved = [];
     *
     * $filesystem->move('source/dir', 'dest/dir', [
     *      'progress' => function(File $file) use (&$filesMoved) {
     *          $filesMoved[] = $file;
     *      }
     * ]);
     *
     * \count($filesMoved);
     *
     * @see FilesystemWriter::move()
     *
     * @param array<string,mixed>|MoveConfig $config
     *
     * @throws NodeExists          If the $destination exists and "fail_if_exists" => true
     * @throws FilesystemException
     */
    public function move(string $source, string $destination, array $config = []): static;

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
     * @param string|Directory<Node>           $path   If {@see Directory}, deletes filtered nodes
     * @param array<string,mixed>|DeleteConfig $config
     *
     * @throws FilesystemException
     */
    public function delete(string|Directory $path = '', array $config = []): static;

    /**
     * @see FilesystemWriter::createDirectory()
     *
     * @param array<string,mixed> $config
     *
     * @throws FilesystemException
     */
    public function mkdir(string $path = '', array $config = []): static;

    /**
     * @see FilesystemWriter::setVisibility()
     *
     * @throws FilesystemException
     */
    public function chmod(string $path, string $visibility): static;

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
     * @param array<string,mixed>|WriteConfig                                                       $config
     *
     * @throws NodeNotFound        If a callable is provided for $value and $path does not exist
     * @throws NodeTypeMismatch    If a callable is provided for $value and $path is a directory
     * @throws NodeExists          If the $path exists and "fail_if_exists" => true
     * @throws FilesystemException
     */
    public function write(string $path, mixed $value, array $config = []): static;

    /**
     * Fetch the last modified node.
     *
     * @example $filesystem->write(...)->last()
     * @example $filesystem->chmod(...)->last()
     * @example $filesystem->copy(...)->last()
     * @example $filesystem->move(...)->last()
     * @example $filesystem->mkdir(...)->last()
     *
     * @return File|Directory<Node>
     *
     * @throws \LogicException If the last operation didn't modify a file
     */
    public function last(): File|Directory;

    /**
     * The name of this filesystem.
     */
    public function name(): string;
}
