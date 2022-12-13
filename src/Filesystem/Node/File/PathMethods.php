<?php

namespace Zenstruck\Filesystem\Node\File;

use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;
use Zenstruck\Filesystem\Node\ProvidesName;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait PathMethods
{
    use ProvidesName;

    public function extension(): ?string
    {
        return \mb_strtolower(\pathinfo($this->path(), \PATHINFO_EXTENSION)) ?: null;
    }

    public function guessExtension(): ?string
    {
        if (\is_string($ext = $this->extension() ?? \array_search($this->mimeType(), GeneratedExtensionToMimeTypeMap::MIME_TYPES_FOR_EXTENSIONS, true))) {
            return $ext;
        }

        return null;
    }

    public function nameWithoutExtension(): string
    {
        return \pathinfo($this->path(), \PATHINFO_FILENAME);
    }

    abstract public function mimeType(): string;
}
