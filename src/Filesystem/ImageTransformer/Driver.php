<?php
declare(strict_types=1);

namespace Zenstruck\Filesystem\ImageTransformer;

use Zenstruck\Filesystem\Adapter\Operator;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
interface Driver
{
    public function loadFromImage(Image $image): mixed;

    public function getContents(mixed $resource): mixed;
}
