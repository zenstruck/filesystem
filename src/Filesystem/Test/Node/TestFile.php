<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Test\Node;

use League\Flysystem\UnableToGeneratePublicUrl;
use Zenstruck\Assert;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\DecoratedFile;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class TestFile extends TestNode implements File
{
    use DecoratedFile;

    public function __construct(private File $inner)
    {
    }

    public function assertContentIs(string $expected): self
    {
        Assert::that($this->contents())->is($expected);

        return $this;
    }

    public function assertContentIsNot(string $expected): self
    {
        Assert::that($this->contents())->isNot($expected);

        return $this;
    }

    public function assertContentContains(string $expected): self
    {
        Assert::that($this->contents())->contains($expected);

        return $this;
    }

    public function assertContentDoesNotContain(string $expected): self
    {
        Assert::that($this->contents())->doesNotContain($expected);

        return $this;
    }

    public function assertMimeTypeIs(string $expected): self
    {
        Assert::that($this->mimeType())->is($expected);

        return $this;
    }

    public function assertMimeTypeIsNot(string $expected): self
    {
        Assert::that($this->mimeType())->isNot($expected);

        return $this;
    }

    public function assertSize(int $expected): self
    {
        Assert::that($this->size())->is($expected);

        return $this;
    }

    public function assertChecksum(string $expected): self
    {
        Assert::that($this->checksum())->is($expected);

        return $this;
    }

    public function dump(): self
    {
        $what = [
            'path' => (string) $this->path(),
            'mimeType' => $this->mimeType(),
            'lastModified' => $this->lastModified(),
            'size' => $this->size(),
        ];

        try {
            $what['public_url'] = $this->publicUrl();
        } catch (UnableToGeneratePublicUrl) {
        }

        if ($this instanceof Image) {
            $what['image']['height'] = $this->dimensions()->height();
            $what['image']['width'] = $this->dimensions()->width();
        }

        \function_exists('dump') ? dump($what) : \var_dump($what);

        return $this;
    }

    protected function inner(): File
    {
        return $this->inner;
    }
}
