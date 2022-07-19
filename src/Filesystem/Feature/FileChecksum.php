<?php

namespace Zenstruck\Filesystem\Feature;

use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface FileChecksum
{
    public function md5Checksum(File $file): string;

    public function sha1Checksum(File $file): string;
}
