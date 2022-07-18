<?php

namespace Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 */
final class FileSize
{
    private const DECIMAL = 1000;
    private const BINARY = 1024;
    private const DECIMAL_UNITS = ['b' => 'B', 'kb' => 'kB', 'mb' => 'MB', 'gb' => 'GB', 'tb' => 'TB', 'pb' => 'PB', 'eb' => 'EB', 'zb' => 'ZB', 'yb' => 'YB'];
    private const BINARY_UNITS = ['b' => 'B', 'kib' => 'KiB', 'mib' => 'MiB', 'gib' => 'GiB', 'tib' => 'TiB', 'pib' => 'PiB', 'eib' => 'EiB', 'zib' => 'ZiB', 'yib' => 'YiB'];

    /** @var array<string,\NumberFormatter> */
    private static array $formatters = [];

    private function __construct(private int $bytes, private int $factor = self::DECIMAL)
    {
    }

    /**
     * @see humanize()
     */
    public function __toString(): string
    {
        return $this->humanize();
    }

    /**
     * Create in the decimal system (ie kb, MB, GB).
     */
    public static function decimal(int $bytes): self
    {
        return new self($bytes, self::DECIMAL);
    }

    /**
     * Create in the binary system (ie KiB, MiB, GiB).
     */
    public static function binary(int $bytes): self
    {
        return new self($bytes, self::BINARY);
    }

    /**
     * Create from bytes (ie 546548) or a human-readable format (ie "1.1kB", "3.42 MiB").
     * Auto determines system from suffix (ie kB = decimal, MiB = binary) if possible,
     * otherwise, defaults to decimal.
     */
    public static function from(string|int $from): self
    {
        if (\is_numeric($from)) {
            return new self((int) $from);
        }

        if (!\preg_match('#^(-?[\d,]+(.[\d,]+)?)([\s\-_]+)?(.+)$#', \trim($from), $matches)) {
            throw new \LogicException(\sprintf('Unable to parse "%s" into a valid file size.', $from));
        }

        return self::build($matches[1], $matches[4]);
    }

    public function bytes(): int
    {
        return $this->bytes;
    }

    /**
     * Convert to binary system (ie MB -> MiB).
     */
    public function asBinary(): self
    {
        $clone = clone $this;
        $clone->factor = self::BINARY;

        return $clone;
    }

    /**
     * Convert to binary system (ie MiB -> MB).
     */
    public function asDecimal(): self
    {
        $clone = clone $this;
        $clone->factor = self::DECIMAL;

        return $clone;
    }

    /**
     * Converts bytes to a human-readable format (ie "1.1 MB", "3.3 KiB").
     */
    public function humanize(string $format = '%s %s'): string
    {
        $i = 0;
        $units = \array_values(self::DECIMAL === $this->factor ? self::DECIMAL_UNITS : self::BINARY_UNITS);
        $quantity = $this->bytes;

        while (($quantity / $this->factor) >= 1 && $i < (\count($units) - 1)) {
            $quantity /= $this->factor;
            ++$i;
        }

        if (!\class_exists(\NumberFormatter::class)) {
            return \sprintf($format, \round($quantity, 2), $units[$i]);
        }

        return \sprintf($format, self::formatter()->format($quantity), $units[$i]);
    }

    /**
     * @param string|int $min Bytes or a human-readable format (ie "1.1 MB"," 3.3 KiB")
     * @param string|int $max Bytes or a human-readable format (ie "1.1 MB", "3.3 KiB")
     */
    public function isWithin(string|int $min, string|int $max): bool
    {
        return $this->isLargerThanOrEqualTo($min) && $this->isSmallerThanOrEqualTo($max);
    }

    /**
     * @param string|int $other Bytes or a human-readable format (ie "1.1 MB"," 3.3 KiB")
     */
    public function isLargerThan(string|int $other): bool
    {
        return $this->compare($other, '>');
    }

    /**
     * @param string|int $other Bytes or a human-readable format (ie "1.1 MB", "3.3 KiB")
     */
    public function isLargerThanOrEqualTo(string|int $other): bool
    {
        return $this->compare($other, '>=');
    }

    /**
     * @param string|int $other Bytes or a human-readable format (ie" 1.1 MB", "3.3 KiB")
     */
    public function isSmallerThan(string|int $other): bool
    {
        return $this->compare($other, '<');
    }

    /**
     * @param string|int $other Bytes or a human-readable format (ie "1.1 MB", "3.3 KiB")
     */
    public function isSmallerThanOrEqualTo(string|int $other): bool
    {
        return $this->compare($other, '<=');
    }

    /**
     * @param string|int $other Bytes or a human-readable format (ie "1.1 MB", "3.3 KiB")
     */
    public function isEqualTo(string|int $other): bool
    {
        return $this->compare($other, '=');
    }

    private function compare(string|int $other, string $operator): bool
    {
        $other = \is_numeric($other) ? (int) $other : self::from($other)->bytes();

        return match ($operator) {
            '>' => $this->bytes() > $other,
            '>=' => $this->bytes() >= $other,
            '<' => $this->bytes() < $other,
            '<=' => $this->bytes() <= $other,
            '=' => $this->bytes() === $other,
            default => throw new \LogicException('Invalid operator.'),
        };
    }

    private static function build(float $quantity, string $units): self
    {
        $lower = \mb_strtolower($units);

        if ('b' === $lower) {
            return new self((int) $quantity);
        }

        $factor = match (true) {
            \array_key_exists($lower, self::DECIMAL_UNITS) => self::DECIMAL,
            \array_key_exists($lower, self::BINARY_UNITS) => self::BINARY,
            default => throw new \InvalidArgumentException(\sprintf('"%s" is an invalid informational unit.', $units)),
        };

        foreach (\array_keys(self::DECIMAL === $factor ? self::DECIMAL_UNITS : self::BINARY_UNITS) as $unit) {
            if ($lower === $unit) {
                break;
            }

            $quantity *= $factor;
        }

        return new self((int) $quantity, $factor);
    }

    private static function formatter(): \NumberFormatter
    {
        if (isset(self::$formatters[$locale = \Locale::getDefault()])) {
            return self::$formatters[$locale];
        }

        self::$formatters[$locale] = new \NumberFormatter(\Locale::getDefault(), \NumberFormatter::DECIMAL);
        self::$formatters[$locale]->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 2);
        self::$formatters[$locale]->setAttribute(\NumberFormatter::ROUNDING_MODE, \NumberFormatter::ROUND_HALFUP);

        return self::$formatters[$locale];
    }
}
