<?php

namespace Zenstruck\Filesystem;

/**
 * Creates a temporary file or wraps an existing file to be deleted
 * at the end of the script.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
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

    /**
     * @param string|resource|ResourceWrapper $contents
     */
    public static function with(mixed $contents): self
    {
        ResourceWrapper::open($file = new self(), 'w')->write($contents)->close();

        \clearstatcache(false, $file);

        return $file;
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

        match (\strtolower($type)) {
            'jpeg', 'jpg' => \imagejpeg($image, (string) $file),
            'png' => \imagepng($image, (string) $file),
            'gif' => \imagegif($image, (string) $file),
            'bmp' => \imagebmp($image, (string) $file),
            'webp' => \imagewebp($image, (string) $file),
            'wbmp' => \imagewbmp($image, (string) $file),
            default => throw new \InvalidArgumentException(\sprintf('"%s" is an invalid image type.', $type)),
        };

        \clearstatcache(false, $file);

        return $file;
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

    public function delete(): self
    {
        if (\file_exists($this)) {
            \unlink($this);
        }

        return $this;
    }

    public function getSize(): int
    {
        \clearstatcache(false, $this);

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
