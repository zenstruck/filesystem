<?php

namespace Zenstruck\Filesystem\Feature\FileUrl;

use Zenstruck\Filesystem\Feature\FileUrl;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PrefixFileUrlFeature implements FileUrl
{
    /** @var string[]|Uri[] */
    private array $prefixes;

    /**
     * @param string[]|Uri[]|Uri|string $prefix
     */
    public function __construct(array|string|Uri $prefix)
    {
        $this->prefixes = \array_values(!\is_array($prefix) ? [$prefix] : $prefix);
    }

    public function urlFor(File $file, mixed $options = []): Uri
    {
        $path = $file->path();

        if (1 === \count($this->prefixes)) {
            return $this->prefix(0)->appendPath($path);
        }

        /**
         * @source https://github.com/symfony/symfony/blob/294195157c3690b869ff6295713a69ff38b3039c/src/Symfony/Component/Asset/UrlPackage.php#L115
         */
        $index = (int) \fmod(\hexdec(\mb_substr(\hash('sha256', $path), 0, 10)), \count($this->prefixes));

        return $this->prefix($index)->appendPath($path);
    }

    private function prefix(int $index): Uri
    {
        $prefix = $this->prefixes[$index] ?? throw new \LogicException('Invalid index.');

        if ($prefix instanceof Uri) {
            return $prefix;
        }

        return $this->prefixes[$index] = Uri::new($prefix);
    }
}
