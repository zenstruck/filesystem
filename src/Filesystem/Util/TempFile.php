<?php

namespace Zenstruck\Filesystem\Util;

use Zenstruck\Filesystem\Node\File;

/**
 * Creates a temporary file or wraps an existing file to be deleted
 * at the end of the script.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class TempFile extends \SplFileInfo
{
    /** @var string[] */
    private static array $created = [];

    public function __construct(?string $filename = null)
    {
        $filename ??= self::tempFile();

        if (\is_dir($filename)) {
            throw new \LogicException("\"{$filename}\" is a directory.");
        }

        parent::__construct($filename);

        if (!\count(self::$created)) {
            // delete on script end
            \register_shutdown_function([self::class, 'purge']);
        }

        self::$created[] = $filename;
    }

    public static function new(?string $filename = null): self
    {
        return new self($filename);
    }

    public static function withExtension(string $extension): self
    {
        $original = self::tempFile();

        if (!\rename($original, $new = "{$original}.{$extension}")) {
            throw new \RuntimeException('Unable to create temp file with extension.');
        }

        return new self($new);
    }

    /**
     * @param string|resource|ResourceWrapper|File $contents
     */
    public static function for(mixed $contents): self
    {
        $close = false;

        if ($contents instanceof File) {
            $contents = ResourceWrapper::wrap($contents->read());
            $close = true;
        }

        ResourceWrapper::open($file = new self(), 'w')->write($contents)->close();

        if ($close && $contents instanceof ResourceWrapper) {
            $contents->close();
        }

        return $file->refresh();
    }

    /**
     * Create temporary image file.
     *
     * @source https://github.com/laravel/framework/blob/183d38f18c0ea9fe13b6d10a6d8360be881d096c/src/Illuminate/Http/Testing/FileFactory.php#L68
     */
    public static function image(int $width = 10, int $height = 10, string $type = 'jpeg'): self
    {
        $file = new self();

        if (false === $image = \imagecreatetruecolor($width, $height)) {
            throw new \RuntimeException('Error creating temporary image.');
        }

        match (\mb_strtolower($type)) {
            'jpeg', 'jpg' => \imagejpeg($image, (string) $file),
            'png' => \imagepng($image, (string) $file),
            'gif' => \imagegif($image, (string) $file),
            'bmp' => \imagebmp($image, (string) $file),
            'webp' => \imagewebp($image, (string) $file),
            'wbmp' => \imagewbmp($image, (string) $file),
            default => throw new \InvalidArgumentException(\sprintf('"%s" is an invalid image type.', $type)),
        };

        return $file->refresh();
    }

    /**
     * Manually delete all created temp files. Useful for long-running
     * processes.
     */
    public static function purge(): void
    {
        foreach (self::$created as $filename) {
            if (\file_exists($filename)) {
                \unlink($filename);
            }
        }
    }

    public function refresh(): self
    {
        \clearstatcache(false, $this);

        return $this;
    }

    public function delete(): self
    {
        if (\file_exists($this)) {
            \unlink($this);
        }

        return $this;
    }

    public function getSize(): int
    {
        $this->refresh();

        return parent::getSize();
    }

    private static function tempFile(): string
    {
        if (false === $filename = \tempnam(\sys_get_temp_dir(), 'zsfs_')) {
            throw new \RuntimeException('Failed to create temporary file.');
        }

        return $filename;
    }
}
