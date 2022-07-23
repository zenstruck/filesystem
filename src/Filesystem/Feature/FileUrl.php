<?php

namespace Zenstruck\Filesystem\Feature;

use Zenstruck\Filesystem\Node\File;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface FileUrl
{
    /**
     * @param array<string,mixed> $options
     */
    public function urlFor(File $file, array $options = []): Uri;
}
