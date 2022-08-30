<?php

namespace Zenstruck\Filesystem;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LoggableFilesystem implements Filesystem
{
    public const DEFAULT_CONFIG = [
        Operation::READ => LogLevel::DEBUG,
        Operation::WRITE => LogLevel::INFO,
    ];

    /**
     * @param array<Operation::*,false|LogLevel::*> $config
     */
    public function __construct(
        private Filesystem $inner,
        private LoggerInterface $logger,
        private array $config = []
    ) {
    }

    public function node(string $path): File|Directory
    {
        $ret = $this->inner->node($path);

        $this->log(
            $this->config[Operation::READ] ?? self::DEFAULT_CONFIG[Operation::READ],
            'Read "{path}" ({type}) on filesystem "{filesystem}"',
            [
                'path' => $path,
                'type' => $ret instanceof File ? 'file' : 'directory',
                'filesystem' => $this->name(),
            ]
        );

        return $ret;
    }

    public function file(string $path): File
    {
        $ret = $this->inner->file($path);

        $this->log(
            $this->config[Operation::READ] ?? self::DEFAULT_CONFIG[Operation::READ],
            'Read "{path}" (file) on filesystem "{filesystem}"',
            [
                'path' => $path,
                'filesystem' => $this->name(),
            ]
        );

        return $ret;
    }

    public function directory(string $path): Directory
    {
        $ret = $this->inner->directory($path);

        $this->log(
            $this->config[Operation::READ] ?? self::DEFAULT_CONFIG[Operation::READ],
            'Read "{path}" (directory) on filesystem "{filesystem}"',
            [
                'path' => $path,
                'filesystem' => $this->name(),
            ]
        );

        return $ret;
    }

    public function image(string $path, array $config = []): Image
    {
        $ret = $this->inner->image($path);

        $this->log(
            $this->config[Operation::READ] ?? self::DEFAULT_CONFIG[Operation::READ],
            'Read "{path}" (image) on filesystem "{filesystem}"',
            [
                'path' => $path,
                'filesystem' => $this->name(),
            ]
        );

        return $ret;
    }

    public function has(string $path): bool
    {
        $ret = $this->inner->has($path);

        $this->log(
            $this->config[Operation::READ] ?? self::DEFAULT_CONFIG[Operation::READ],
            'Checked existence of "{path}" on filesystem "{filesystem}"',
            [
                'path' => $path,
                'filesystem' => $this->name(),
            ]
        );

        return $ret;
    }

    public function copy(string $source, string $destination, array $config = []): static
    {
        $this->inner->copy($source, $destination, $config);

        $this->log(
            $this->config[Operation::COPY] ?? $this->config[Operation::WRITE] ?? self::DEFAULT_CONFIG[Operation::WRITE],
            'Copied "{source}" to "{destination}" on filesystem "{filesystem}"',
            [
                'source' => $source,
                'destination' => $destination,
                'filesystem' => $this->name(),
            ]
        );

        return $this;
    }

    public function move(string $source, string $destination, array $config = []): static
    {
        $this->inner->move($source, $destination, $config);

        $this->log(
            $this->config[Operation::MOVE] ?? $this->config[Operation::WRITE] ?? self::DEFAULT_CONFIG[Operation::WRITE],
            'Moved "{source}" to "{destination}" on filesystem "{filesystem}"',
            [
                'source' => $source,
                'destination' => $destination,
                'filesystem' => $this->name(),
            ]
        );

        return $this;
    }

    public function delete(Directory|string $path, array $config = []): static
    {
        $this->inner->delete($path, $config);

        $this->log(
            $this->config[Operation::DELETE] ?? $this->config[Operation::WRITE] ?? self::DEFAULT_CONFIG[Operation::WRITE],
            'Deleted "{path}" on filesystem "{filesystem}"',
            [
                'path' => (string) $path,
                'filesystem' => $this->name(),
            ]
        );

        return $this;
    }

    public function mkdir(string $path, array $config = []): static
    {
        $this->inner->mkdir($path, $config);

        $this->log(
            $this->config[Operation::MKDIR] ?? $this->config[Operation::WRITE] ?? self::DEFAULT_CONFIG[Operation::WRITE],
            'Created directory "{path}" on filesystem "{filesystem}"',
            [
                'path' => $path,
                'filesystem' => $this->name(),
            ]
        );

        return $this;
    }

    public function chmod(string $path, string $visibility): static
    {
        $this->inner->chmod($path, $visibility);

        $this->log(
            $this->config[Operation::CHMOD] ?? $this->config[Operation::WRITE] ?? self::DEFAULT_CONFIG[Operation::WRITE],
            'Set visibility of "{path}" to "{visibility}" on filesystem "{filesystem}"',
            [
                'path' => $path,
                'filesystem' => $this->name(),
                'visibility' => $visibility,
            ]
        );

        return $this;
    }

    public function write(string $path, mixed $value, array $config = []): static
    {
        $this->inner->write($path, $value, $config);

        $this->log(
            $this->config[Operation::WRITE] ?? self::DEFAULT_CONFIG[Operation::WRITE],
            'Wrote "{what}" to "{path}" on filesystem "{filesystem}"',
            [
                'what' => \get_debug_type($value),
                'path' => $path,
                'filesystem' => $this->name(),
            ]
        );

        return $this;
    }

    public function last(): File|Directory
    {
        return $this->inner->last();
    }

    public function name(): string
    {
        return $this->inner->name();
    }

    /**
     * @param string|false        $level
     * @param array<string,mixed> $context
     */
    private function log(string|bool $level, string $message, array $context = []): void
    {
        if (\is_bool($level)) {
            return;
        }

        $this->logger->log($level, $message, $context);
    }
}
