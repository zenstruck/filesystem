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
use Zenstruck\Filesystem\Node\Metadata;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-import-type Format from Metadata
 * @phpstan-import-type Serialized from Metadata
 */
class SerializableFile implements File, \JsonSerializable
{
    use DecoratedFile, DecoratedNode;

    /**
     * @param Format $metadata
     */
    public function __construct(private File $file, private string|array $metadata)
    {
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
        return Metadata::serialize($this, $this->metadata);
    }

    protected function inner(): File
    {
        return $this->file;
    }

    /**
     * @return list<array{0:string,1:string|null}>
     */
    private static function parseMetadata(int|string $key, array|string $value): array
    {
        if (\is_int($key) && \is_string($value)) {
            return [[$value, null]];
        }

        if (\is_string($value)) {
            return [[$key, $value]];
        }

        if (\is_int($key)) {
            throw new \LogicException('Invalid metadata format.');
        }

        return \array_map(static fn(string $v) => [$key, $v], $value);
    }
}
