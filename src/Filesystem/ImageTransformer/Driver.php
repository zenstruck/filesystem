<?php

namespace Zenstruck\Filesystem\ImageTransformer;

use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
interface Driver
{
    public function loadFromImage(Image $image): mixed;

    public function getContents(mixed $resource): mixed;
}
