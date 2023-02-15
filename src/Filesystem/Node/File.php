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
     */
    public function guessExtension(): ?string;

    public function mimeType(): string;

    public function size(): int;

    public function contents(): string;

    /**
     * @return resource
     */
    public function read();

    public function stream(): Stream;

    public function checksum(?string $algo = null): string;

    public function publicUrl(array $config = []): string;

    public function temporaryUrl(\DateTimeInterface|string $expires, array $config = []): string;

    /**
     * Create a temporary, "real, local file". This file is deleted at the
     * end of the script.
     */
    public function tempFile(): \SplFileInfo;
}
