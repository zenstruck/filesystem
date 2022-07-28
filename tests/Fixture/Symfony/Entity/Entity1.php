<?php

namespace Zenstruck\Filesystem\Tests\Fixture\Symfony\Entity;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;

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

    #[ORM\Column(type: 'image', nullable: true, options: ['filesystem' => 'public'])]
    public ?Image $image = null;
}
