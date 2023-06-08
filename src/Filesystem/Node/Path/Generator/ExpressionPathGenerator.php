<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node\Path\Generator;

use Symfony\Component\String\ByteString;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\Path\Generator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ExpressionPathGenerator implements Generator
{
    private const DEFAULT_EXPRESSION = '{name}-{rand}{ext}';
    private const ALPHABET = '123456789abcdefghijkmnopqrstuvwxyz';

    public function __construct(private ?SluggerInterface $slugger = null)
    {
    }

    public function generatePath(Node $node, array $context = []): string
    {
        $context['node'] = $node;

        if ($node instanceof File) {
            $context['file'] = $node;
        }

        return (string) \preg_replace_callback(
            '#{([\w.:\-\[\]]+)(\(([\w\s,\-]+)?\))?(\|(slug|slugify|lower|upper))?}#',
            function($matches) use ($node, $context) {
                $value = match ($matches[1]) {
                    'name' => $this->slugify($node->path()->basename()),
                    'ext' => self::extensionWithDot($node),
                    'checksum' => $node->ensureFile()->checksum(),
                    'rand' => self::randomString(),
                    default => self::parseVariable($matches[1], $matches[3] ?? null, $node, $context),
                };

                return match ($matches[5] ?? null) {
                    'slug', 'slugify' => $this->slugify($value),
                    'lower' => \mb_strtolower($value),
                    'upper' => \mb_strtoupper($value),
                    default => $value,
                };
            },
            (string) ($context['expression'] ?? self::DEFAULT_EXPRESSION)
        );
    }

    private static function randomString(int $length = 6): string
    {
        if (!\class_exists(ByteString::class)) {
            /**
             * @source https://stackoverflow.com/a/13212994
             */
            return \mb_substr(\str_shuffle(\str_repeat(self::ALPHABET, (int) \ceil($length / \mb_strlen(self::ALPHABET)))), 1, $length);
        }

        return ByteString::fromRandom($length, self::ALPHABET)->toString();
    }

    private static function extensionWithDot(Node $node): string
    {
        if (!$ext = $node->path()->extension()) {
            return '';
        }

        return '.'.\mb_strtolower($ext);
    }

    private static function checksum(File $file, ?string $algorithm, ?int $length): string
    {
        $checksum = $file->checksum($algorithm);

        return $length ? \mb_substr($checksum, 0, $length) : $checksum;
    }

    private function slugify(string $value): string
    {
        if (!$this->slugger && \interface_exists(SluggerInterface::class) && \interface_exists(LocaleAwareInterface::class)) {
            $this->slugger = new AsciiSlugger();
        }

        return \mb_strtolower($this->slugger ? $this->slugger->slug($value) : \str_replace(' ', '-', $value));
    }

    private static function parseVariable(string $variable, ?string $arguments, Node $node, array $context): string // @phpstan-ignore-line
    {
        if (\count($parts = \explode(':', $variable)) > 1) {
            return match (\mb_strtolower($parts[0])) {
                'checksum' => self::parseChecksum($node->ensureFile(), $parts),
                'rand' => self::randomString((int) $parts[1]),
                default => throw new \LogicException(\sprintf('Unable to parse expression variable {%s}.', $variable)),
            };
        }

        $value = self::parseVariableValue(
            $variable,
            null === $arguments ? [] : \array_map(static fn(string $v) => \trim($v), \explode(',', $arguments)),
            $context
        );

        if (null === $value || \is_scalar($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        throw new \LogicException(\sprintf('Unable to parse expression variable {%s}.', $variable));
    }

    private static function parseVariableValue(string $variable, array $arguments, array $context): mixed // @phpstan-ignore-line
    {
        if (\array_key_exists($variable, $context)) {
            return $context[$variable];
        }

        return self::dotAccess($context, $variable, $arguments);
    }

    private static function parseChecksum(File $file, array $parts): string // @phpstan-ignore-line
    {
        unset($parts[0]); // removes "checksum"

        foreach ($parts as $part) {
            match (true) {
                \is_numeric($part) => $length = (int) $part,
                default => $algorithm = $part,
            };
        }

        return self::checksum($file, $algorithm ?? null, $length ?? null);
    }

    /**
     * Quick and dirty "dot" accessor that works for objects and arrays.
     */
    private static function dotAccess(object|array &$what, string $path, array $arguments): mixed // @phpstan-ignore-line
    {
        $current = &$what;

        foreach (\explode('.', $path) as $segment) {
            if (\is_array($current) && \array_key_exists($segment, $current)) {
                $current = &$current[$segment];

                continue;
            }

            if (!\is_object($current)) {
                throw new \InvalidArgumentException(\sprintf('Unable to access "%s".', $path));
            }

            if (\method_exists($current, $segment)) {
                $current = $current->{$segment}(...$arguments);

                continue;
            }

            foreach (['get', 'has', 'is'] as $prefix) {
                if (\method_exists($current, $method = $prefix.\ucfirst($segment))) {
                    $current = $current->{$method}(...$arguments);

                    continue 2;
                }
            }

            if (\property_exists($current, $segment)) {
                $current = &$current->{$segment};

                continue;
            }

            throw new \InvalidArgumentException(\sprintf('Unable to access "%s".', $path));
        }

        return $current;
    }
}
