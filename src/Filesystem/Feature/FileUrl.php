<?php

namespace Zenstruck\Filesystem\Feature;

use Zenstruck\Filesystem\Node\File;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface FileUrl
{
    public function urlFor(File $file): Uri;
}
