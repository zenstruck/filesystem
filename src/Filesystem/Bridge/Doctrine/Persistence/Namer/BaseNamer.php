<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer;

use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer;
use Zenstruck\Filesystem\Node\File\PendingFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class BaseNamer implements Namer
{
    private static AsciiSlugger $asciiSlugger;

    public function __construct(protected ?SluggerInterface $slugger = null)
    {
        if (!$this->slugger && \interface_exists(LocaleAwareInterface::class) && \class_exists(AsciiSlugger::class)) {
            $this->slugger = self::$asciiSlugger ??= new AsciiSlugger();
        }
    }

    final protected static function extensionWithDot(PendingFile $file): string
    {
        if (!$ext = $file->originalExtension()) {
            return '';
        }

        return '.'.\mb_strtolower($ext);
    }

    final protected function slugify(string $value): string
    {
        return \mb_strtolower($this->slugger ? $this->slugger->slug($value) : \str_replace(' ', '-', $value)); // quick and dirty
    }
}
