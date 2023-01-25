<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Symfony\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Filesystem\Doctrine\Mapping as Filesystem;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\Image\PlaceholderImage;
use Zenstruck\Filesystem\Node\File\PlaceholderFile;

#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['person' => self::class, 'employee' => Entity2::class])]
class Entity1
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[Filesystem\StoreAsPath('public', namer: 'expression:files/{this.title|slug}-{checksum:7}{ext}')]
    private ?File $file1 = null;

    #[Filesystem\StoreAsPath(
        filesystem: 'public',
        namer: 'expression:images/{this.title|slug}-{checksum:7}{ext}',
        deleteOnRemove: false,
        deleteOnUpdate: false,
    )]
    private ?Image $image1 = null;

    #[Filesystem\Stateless('public', namer: 'expression:{this.title|slug}.txt')]
    private File $virtualFile1;

    #[Filesystem\Stateless('public', namer: 'expression:{this.title|slug}.jpg')]
    private Image $virtualImage1;

    public function __construct(string $title)
    {
        $this->title = $title;
        $this->virtualFile1 = new PlaceholderFile();
        $this->virtualImage1 = new PlaceholderImage();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getFile1(): ?File
    {
        return $this->file1;
    }

    public function setFile1(?File $file1): void
    {
        $this->file1 = $file1;
    }

    public function getImage1(): ?Image
    {
        return $this->image1;
    }

    public function setImage1(?Image $image1): void
    {
        $this->image1 = $image1;
    }

    public function getVirtualFile1(): ?File
    {
        return $this->virtualFile1->exists() ? $this->virtualFile1 : null;
    }

    public function getVirtualImage1(): ?File
    {
        return $this->virtualImage1->exists() ? $this->virtualImage1 : null;
    }
}
