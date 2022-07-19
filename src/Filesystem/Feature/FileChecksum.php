<?php

namespace Zenstruck\Filesystem\Feature;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface FileChecksum
{
    public function md5Checksum(string $path): string;

    public function sha1Checksum(string $path): string;
}
