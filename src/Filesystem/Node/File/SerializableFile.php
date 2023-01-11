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

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class SerializableFile implements File
{
    use DecoratedFile, DecoratedNode;

    /**
     * @param array<int,string>|array<string,list<string>> $metadata
     */
    public function __construct(private File $file, private array $metadata)
    {
    }

    /**
     * @return array<string,scalar|array<string,scalar>>
     */
    public function serialize(): array
    {
        $ret = [];

        foreach ($this->metadata as $key => $value) {
            if (\is_int($key) && \is_string($value)) {
                $ret[$value] = $this->{$value}();

                continue;
            }

            if (!\is_string($key) || !\is_array($value)) {
                throw new \LogicException('Invalid metadata format.');
            }

            foreach ($value as $argument) {
                $ret[$key][$argument] = $this->{$key}($argument);
            }
        }

        return $ret;
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
