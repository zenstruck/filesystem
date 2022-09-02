<?php

namespace Zenstruck\Filesystem\Node;

use Zenstruck\Dimension\Information;
use Zenstruck\Filesystem\Exception\UnsupportedFeature;
use Zenstruck\Filesystem\Feature\FileUrl;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\File\Checksum;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 */
interface File extends Node
{
    /**
     * Create a temporary, "real, local file". This file is deleted at the
     * end of the script.
     */
    public function tempFile(): \SplFileInfo;

    /**
     * @see https://github.com/zenstruck/dimension#information-object
     */
    public function size(): Information;

    public function mimeType(): string;

    public function contents(): string;

    /**
     * @return resource
     */
    public function read();

    /**
     * Calculate the checksum for the file. Defaults to md5.
     *
     * @example $file->checksum()->toString() (md5 hash of contents)
     * @example $file->checksum()->sha1()->toString() (sha1 hash of contents)
     * @example $file->checksum()->metadata()->toString() (md5 hash of file size + last modified timestamp + mime-type)
     * @example $file->checksum()->metadata()->sha1()->toString() (sha1 hash of file size + last modified timestamp + mime-type)
     */
    public function checksum(): Checksum;

    /**
     * Whether the contents of this file and another match (content checksum is used).
     */
    public function contentsEquals(self $other): bool;

    /**
     * Whether the metadata of this file and another match (metadata checksum is used).
     */
    public function metadataEquals(self $other): bool;

    /**
     * @return Directory<Node>
     */
    public function directory(): Directory;

    public function extension(): ?string;

    /**
     * Returns the file extension if available. If not, and symfony/mime
     * is installed, attempt to guess from mime-type.
     */
    public function guessExtension(): ?string;

    /**
     * @example If $path is "foo/bar/baz.txt", returns "baz"
     * @example If $path is "foo/bar/baz", returns "baz"
     */
    public function nameWithoutExtension(): string;

    /**
     * @param array<string,mixed> $options
     *
     * @throws UnsupportedFeature If your adapter does not support {@see FileUrl}
     */
    public function url(array $options = []): Uri;
}
