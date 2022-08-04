<?php

namespace Zenstruck\Filesystem\Tests\Fixture\Symfony\Entity;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\FileCollection;

#[ORM\Entity]
class Entity1
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column]
    public string $title = 'default';

    #[ORM\Column(type: 'file', nullable: true, options: ['filesystem' => 'public'])]
    public ?File $file = null;

    #[ORM\Column(type: 'file', nullable: true, options: ['filesystem' => 'public', 'namer' => 'slugify'])]
    public ?File $fileSlugify = null;

    #[ORM\Column(type: 'file', nullable: true, options: ['filesystem' => 'public', 'namer' => 'checksum'])]
    public ?File $fileChecksum = null;

    #[ORM\Column(type: 'file', nullable: true, options: ['filesystem' => 'public', 'namer' => 'expression', 'expression' => 'foo/bar/{name}{ext}'])]
    public ?File $fileExpression = null;

    #[ORM\Column(type: 'file', nullable: true, options: ['filesystem' => 'public', 'namer' => 'twig', 'template' => 'file_twig.twig'])]
    public ?File $fileTwig = null;

    #[ORM\Column(type: 'file', nullable: true, options: ['filesystem' => 'public', 'namer' => 'expression_language', 'expression' => '"foo/bar/"~object.id~"/"~file.checksum()~"-"~name~ext'])]
    public ?File $fileExpressionLanguage = null;

    #[ORM\Column(type: 'file_collection', nullable: true, options: ['filesystem' => 'public'])]
    public ?FileCollection $collection;
}
