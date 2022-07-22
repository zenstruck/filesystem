<?php

namespace Zenstruck\Filesystem\Node\File;

use League\Flysystem\UnableToRetrieveMetadata;
use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class Image extends File
{
    /** @var array{0:int,1:int} */
    private array $imageSize;

    protected function __construct()
    {
    }

    public function height(): int
    {
        return $this->imageSize()[1];
    }

    public function width(): int
    {
        return $this->imageSize()[0];
    }

    public function aspectRatio(): float
    {
        return $this->width() / $this->height();
    }

    public function refresh(): static
    {
        unset($this->imageSize);

        return parent::refresh();
    }

    /**
     * @return array{0:int,1:int}
     */
    private function imageSize(): array
    {
        if (isset($this->imageSize)) {
            return $this->imageSize;
        }

        $file = $this->operator()->realFile($this);

        if ('image/svg+xml' === $this->mimeType()) {
            return $this->imageSize = self::parseSvg($file) ?? throw UnableToRetrieveMetadata::create($this->path(), 'image_metadata', 'Unable to load svg.');
        }

        if (false === $imageSize = @\getimagesize($file)) {
            throw UnableToRetrieveMetadata::create($this->path(), 'image_size');
        }

        return $this->imageSize = $imageSize; // @phpstan-ignore-line
    }

    /**
     * @return null|array{0:int,1:int}
     */
    private static function parseSvg(\SplFileInfo $file): ?array
    {
        if (false === $xml = \file_get_contents($file)) {
            return null;
        }

        if (false === $xml = \simplexml_load_string($xml)) {
            return null;
        }

        if (!$xml = $xml->attributes()) {
            return null;
        }

        return [
            (int) \round((float) $xml->width),
            (int) \round((float) $xml->height),
        ];
    }
}
