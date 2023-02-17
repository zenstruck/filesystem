<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node;

use Zenstruck\Filesystem\Node\Path\Expression;
use Zenstruck\Filesystem\Node\Path\Namer;
use Zenstruck\Filesystem\Twig\Template;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @readonly
 *
 * @phpstan-import-type Format from Metadata
 */
class Mapping
{
    public const METADATA = 'metadata';
    public const FILESYSTEM = 'filesystem';
    public const NAMER = 'namer';

    /** @var Format */
    public string|array $metadata;

    private ?Namer $namer;

    /**
     * @param Format $metadata
     */
    public function __construct(
        string|array $metadata,
        private ?string $filesystem = null,
        string|Namer|null $namer = null,
    ) {
        $this->metadata = $metadata;
        $this->namer = self::parseNamer($namer);

        if (!$this->filesystem && $this->requiresFilesystem()) {
            throw new \LogicException('A filesystem is required if not serializing the DSN.');
        }

        if (!$this->namer && $this->requiresPathGenerator()) {
            throw new \LogicException('A namer is required if not serializing the DSN or path.');
        }
    }

    /**
     * @internal
     */
    public static function fromArray(array $array): self
    {
        $filesystem = $array[self::FILESYSTEM] ?? null;

        if ($filesystem instanceof self) {
            return $filesystem;
        }

        return new self(
            $array[self::METADATA] ?? (isset($array[self::FILESYSTEM]) ? Metadata::PATH : Metadata::DSN),
            $filesystem,
            $array[self::NAMER] ?? null,
        );
    }

    /**
     * @internal
     */
    public function filesystem(): ?string
    {
        return $this->filesystem;
    }

    /**
     * @internal
     */
    public function namer(): ?Namer
    {
        return $this->namer;
    }

    /**
     * @internal
     */
    final public function requiresPathGenerator(): bool
    {
        if (\is_string($this->metadata)) {
            return false;
        }

        return !\in_array(Metadata::DSN, $this->metadata, true) && !\in_array(Metadata::PATH, $this->metadata, true);
    }

    private function requiresFilesystem(): bool
    {
        if (Metadata::DSN === $this->metadata) {
            return false;
        }

        if (\is_array($this->metadata) && \in_array(Metadata::DSN, $this->metadata, true)) {
            return false;
        }

        return true;
    }

    private static function parseNamer(string|Namer|null $namer): ?Namer
    {
        if (null === $namer || $namer instanceof Namer) {
            return $namer;
        }

        if (2 !== \count($parts = \explode(':', $namer, 2))) {
            return new Namer($namer);
        }

        return match ($parts[0]) {
            'expression' => new Expression($parts[1]),
            'twig' => new Template($parts[1]),
            default => new Namer($namer),
        };
    }
}
