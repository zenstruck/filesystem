<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node\File;

use Zenstruck\Filesystem\Node\DecoratedNode;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\Mapping;
use Zenstruck\Filesystem\Node\Path\Namer;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type Format from Mapping
 * @phpstan-import-type Serialized from Mapping
 */
class SerializableFile implements File, \JsonSerializable
{
    use DecoratedFile, DecoratedNode;

    private Mapping $mapping;

    /**
     * @param Format $metadata
     */
    public function __construct(private File $file, string|array $metadata)
    {
        $this->mapping = new Mapping($metadata, filesystem: 'none', namer: new Namer('none'));
    }

    /**
     * @return Serialized
     */
    public function jsonSerialize(): string|array
    {
        return $this->serialize();
    }

    /**
     * @return Serialized
     */
    public function serialize(): string|array
    {
        return $this->mapping->serialize($this);
    }

    protected function inner(): File
    {
        return $this->file;
    }
}
