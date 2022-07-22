<?php

namespace Zenstruck\Filesystem\Node\File;

use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\PendingNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PendingFile extends File implements PendingNode
{
    use IsPendingFile;
}
