<?php

namespace Zenstruck\Filesystem\Node;

use Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
interface PendingNode extends Node
{
    public function localFile(): \SplFileInfo;

    public function originalName(): string;

    public function originalNameWithoutExtension(): string;

    public function originalExtension(): ?string;

    public function originalExtensionWithDot(): ?string;
}
