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
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Image\LocalImage;
use Zenstruck\Image\TransformerRegistry;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PendingImage extends PendingFile implements Image
{
    use DecoratedImage;

    public function __construct(\SplFileInfo|string $filename, private ?TransformerRegistry $transformerRegistry = null)
    {
        parent::__construct($filename);
    }

    /**
     * @template T of object
     *
     * @param object|callable(T):T $filter
     */
    public function transformInPlace(object|callable $filter, array $options = []): self
    {
        $this->transform($filter, \array_merge($options, ['output' => $this]));

        return $this->refresh();
    }

    public function transform(callable|object $filter, array $options = []): self
    {
        return new self(
            $this->transformerRegistry()->transform($this, $filter, $options),
            $this->transformerRegistry()
        );
    }

    public function transformer(string $class): object
    {
        return $this->transformerRegistry()->get($class)->object($this);
    }

    public function transformUrl(array|string $filter): string
    {
        throw new \BadMethodCallException(\sprintf('%s is not supported for %s.', __METHOD__, static::class));
    }

    protected function localImage(): LocalImage
    {
        return $this->localImage ??= new LocalImage($this);
    }

    private function transformerRegistry(): TransformerRegistry
    {
        return $this->transformerRegistry ??= new TransformerRegistry();
    }
}
