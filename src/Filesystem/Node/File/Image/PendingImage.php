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

use Psr\Http\Message\UploadedFileInterface;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Image as LocalImage;
use Zenstruck\Image\TransformerRegistry;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
final class PendingImage extends PendingFile implements Image
{
    use DecoratedImage;

    private bool $isPsrFile = false;

    public function __construct(\SplFileInfo|string|UploadedFileInterface $filename, private ?TransformerRegistry $transformerRegistry = null)
    {
        parent::__construct($filename);

        if ($filename instanceof UploadedFileInterface && !$filename instanceof \SplFileInfo) {
            $this->isPsrFile = true;
        }
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
        $file = $this->isPsrFile ? $this->localImage() : $this;

        return new self(
            $this->transformerRegistry()->transform($file, $filter, $options),
            $this->transformerRegistry()
        );
    }

    public function transformUrl(array|string $filter): string
    {
        throw new \BadMethodCallException(\sprintf('%s is not supported for %s.', __METHOD__, static::class));
    }

    protected function localImage(): LocalImage
    {
        if ($this->isPsrFile) {
            return $this->localImage = $this->tempFile();
        }

        return $this->localImage ??= new LocalImage($this);
    }

    private function transformerRegistry(): TransformerRegistry
    {
        return $this->transformerRegistry ??= new TransformerRegistry();
    }
}
