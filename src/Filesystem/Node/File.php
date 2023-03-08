<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemReader;
use League\Flysystem\UnableToGeneratePublicUrl;
use League\Flysystem\UnableToGenerateTemporaryUrl;
use League\Flysystem\UnableToProvideChecksum;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use Zenstruck\Filesystem\Node;
use Zenstruck\Stream;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface File extends Node
{
    /**
     * Returns the file extension if available. If not, attempt to
     * guess from mime-type.
     *
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function guessExtension(): ?string;

    /**
     * @see FilesystemReader::mimeType()
     *
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function mimeType(): string;

    /**
     * @see FilesystemReader::fileSize()
     *
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function size(): int;

    /**
     * @see FilesystemReader::read()
     *
     * @throws UnableToReadFile
     * @throws FilesystemException
     */
    public function contents(): string;

    /**
     * @see FilesystemReader::readStream()
     *
     * @return resource
     *
     * @throws UnableToReadFile
     * @throws FilesystemException
     */
    public function read();

    /**
     * Alias for {@see read()} but wraps the resource in a {@see Stream} object.
     *
     * @throws UnableToReadFile
     * @throws FilesystemException
     */
    public function stream(): Stream;

    /**
     * @see FilesystemReader::checksum()
     *
     * @throws UnableToProvideChecksum
     * @throws FilesystemException
     */
    public function checksum(?string $algo = null): string;

    /**
     * @see FilesystemReader::publicUrl()
     *
     * @throws UnableToGeneratePublicUrl
     * @throws FilesystemException
     */
    public function publicUrl(array $config = []): string;

    /**
     * @see FilesystemReader::temporaryUrl()
     *
     * @throws UnableToGenerateTemporaryUrl
     * @throws FilesystemException
     */
    public function temporaryUrl(\DateTimeInterface|string $expires, array $config = []): string;

    /**
     * Create a temporary, "real, local file". This file is deleted at the
     * end of the script.
     */
    public function tempFile(): \SplFileInfo;
}
