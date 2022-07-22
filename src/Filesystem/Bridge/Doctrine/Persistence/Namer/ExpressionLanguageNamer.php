<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\String\Slugger\SluggerInterface;
use Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ExpressionLanguageNamer extends BaseNamer
{
    public function __construct(private ExpressionLanguage $expr, ?SluggerInterface $slugger = null)
    {
        parent::__construct($slugger);
    }

    public function generateName(Node $node, object $object, array $config = []): string
    {
        return $this->expr->evaluate(
            $config['expression'] ?? throw new \LogicException('An "expression" option must be added to your column options.'),
            [
                'node' => $node,
                'file' => $node,
                'this' => $object,
                'ext' => self::extensionWithDot($node),
                'name' => $this->slugify(self::nameWithoutExtension($node)),
                'slugger' => $this->slugger,
            ]
        );
    }
}
