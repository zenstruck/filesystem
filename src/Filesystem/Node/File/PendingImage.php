<?php

namespace Zenstruck\Filesystem\Node\File;

use Zenstruck\Filesystem\Node\PendingNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PendingImage extends Image implements PendingNode
{
    use IsPendingFile;
}
