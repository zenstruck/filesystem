<?php

namespace Zenstruck\Filesystem\Feature;

use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface ModifyFile
{
    /**
     * @param callable(\SplFileInfo):\SplFileInfo $callback
     */
    public function modifyFile(File $file, callable $callback): \SplFileInfo;
}
