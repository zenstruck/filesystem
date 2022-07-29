<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\String\Slugger\SluggerInterface;
use Zenstruck\Filesystem\Node\File\PendingFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ExpressionLanguageNamer extends BaseNamer
{
    public function __construct(private ExpressionLanguage $expr, ?SluggerInterface $slugger = null)
    {
        parent::__construct($slugger);
    }

    public function generateName(PendingFile $file, object $object, array $config = []): string
    {
        return $this->expr->evaluate(
            $config['expression'] ?? throw new \LogicException('An "expression" option must be added to your column options.'),
            [
                'file' => $file,
                'object' => $object,
                'ext' => self::extensionWithDot($file),
                'name' => $this->slugify($file->originalNameWithoutExtension()),
                'slugger' => $this->slugger,
            ]
        );
    }
}
