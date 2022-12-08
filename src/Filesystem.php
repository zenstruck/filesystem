<?php

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
    /**
     * @return File|Directory<Node>
     *
     * @throws NodeNotFound if the path does not exist
     */
    public function node(string $path): File|Directory;

    /**
     * @throws NodeNotFound     if the path does not exist
     * @throws NodeTypeMismatch if the node at path is not a file
     */
    public function file(string $path): File;

    /**
     * @return Directory<Node>
     *
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
     * @param string|Directory<Node> $path
     */
    public function delete(string|Directory $path, array $config = []): static;

    public function mkdir(string $path, array $config = []): static;

    public function chmod(string $path, string $visibility): static;

    /**
     * @param resource|string|\SplFileInfo|File $value
     */
    public function write(string $path, mixed $value, array $config = []): File;
}
