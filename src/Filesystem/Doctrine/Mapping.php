<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Doctrine;

use Zenstruck\Filesystem\Node\Metadata;
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
abstract class Mapping
{
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
        array $namerContext = [],
    ) {
        $this->namer = self::parseNamer($namer, $namerContext);

        if (!$this->filesystem && Metadata::DSN !== $metadata && !isset($metadata[Metadata::DSN])) {
            throw new \LogicException('A filesystem is required if not storing the DSN.');
        }

        $this->metadata = $metadata;

        if (!$this->namer && $this->requiresPathGenerator()) {
            throw new \LogicException('A namer is required if not storing the DSN or path.');
        }
    }

    public function filesystem(): ?string
    {
        return $this->filesystem;
    }

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

    private static function parseNamer(string|Namer|null $namer, array $context): ?Namer
    {
        if (null === $namer) {
            return null;
        }

        if ($namer instanceof Namer) {
            return $namer->with($context);
        }

        if (2 !== \count($parts = \explode(':', $namer, 2))) {
            return new Namer($namer, $context);
        }

        return match ($parts[0]) {
            'expression' => new Expression($parts[1], $context),
            'twig' => new Template($parts[1], $context),
            default => new Namer($namer, $context),
        };
    }
}
