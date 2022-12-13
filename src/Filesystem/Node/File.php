<?php

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

    public function size(): int;

    public function contents(): string;

    public function read(): Stream;

    public function checksum(?string $algo = null): string;

    public function publicUrl(array $config = []): string;

    public function temporaryUrl(\DateTimeInterface $expiresAt, array $config = []): string;

    /**
     * Create a temporary, "real, local file". This file is deleted at the
     * end of the script.
     */
    public function tempFile(): \SplFileInfo;
}
