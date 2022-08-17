<?php

namespace Zenstruck\Filesystem\Feature;

use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Uri;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
interface GlideUrl
{
    public function glideUrlFor(Image $image, mixed $options = []): Uri;
}
