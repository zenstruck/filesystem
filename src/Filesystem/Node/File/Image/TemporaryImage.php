<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node\File\Image;

use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\TemporaryFile;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
final class TemporaryImage extends TemporaryFile implements Image
{
    use DecoratedImage;
}
