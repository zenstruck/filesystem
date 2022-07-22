<?php

namespace Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
interface PendingNode
{
    public function localFile(): \SplFileInfo;
}
