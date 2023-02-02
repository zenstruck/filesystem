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
    public function name(): string;

    /**
     * @throws NodeNotFound if the path does not exist
     */
    public function node(string $path): Node;

    /**
     * @throws NodeNotFound     if the path does not exist
     * @throws NodeTypeMismatch if the node at path is not a file
     */
    public function file(string $path): File;

    /**
     * @throws NodeNotFound     if the path does not exist
     * @throws NodeTypeMismatch if the node at path is not a directory
     */
    public function directory(string $path = ''): Directory;

    /**
     * @throws NodeNotFound     if the path does not exist
     * @throws NodeTypeMismatch if the node at path is not an image file
     */
    public function image(string $path): Image;

    public function has(string $path): bool;

    public function copy(string $source, string $destination, array $config = []): static;

    public function move(string $source, string $destination, array $config = []): static;

    /**
     * @param array{
     *     progress?: callable(Node=):void
     * } $config
     */
    public function delete(string|Directory $path, array $config = []): static;

    public function mkdir(string $path, array $config = []): static;

    public function chmod(string $path, string $visibility): static;

    /**
     * @param resource|string|\SplFileInfo|Node $value
     * @param array{
     *     progress?: callable(File=):void
     * } $config
     */
    public function write(string $path, mixed $value, array $config = []): static;

    /**
     * @throws \LogicException if no last node available
     */
    public function last(): Node;
}
