<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer;

use Symfony\Component\String\Slugger\SluggerInterface;
use Twig\Environment;
use Zenstruck\Filesystem\Node\File\PendingFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TwigNamer extends BaseNamer
{
    public function __construct(private Environment $twig, ?SluggerInterface $slugger = null)
    {
        parent::__construct($slugger);
    }

    public function generateName(PendingFile $file, object $object, array $config = []): string
    {
        return \str_replace(["\r", "\n"], '', \trim($this->twig->render(
            $config['template'] ?? throw new \LogicException('A "template" option must be added to your column options.'),
            [
                'file' => $file,
                'object' => $object,
                'name' => $this->slugify($file->originalNameWithoutExtension()),
                'ext' => self::extensionWithDot($file),
            ]
        )));
    }
}
