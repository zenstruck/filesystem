<?php

namespace Zenstruck\Filesystem\Feature;

use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface FileChecksum
{
    public function md5ChecksumFor(File $file): string;

    public function sha1ChecksumFor(File $file): string;
}
