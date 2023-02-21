<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Filesystem\Doctrine\Mapping as Filesystem;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\Directory\PlaceholderDirectory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\Image\PlaceholderImage;
use Zenstruck\Filesystem\Node\File\PlaceholderFile;
use Zenstruck\Filesystem\Node\Metadata;
use Zenstruck\Tests\Fixtures\CustomObjectPathGenerator;

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

    #[ORM\Column('_unique', unique: true, nullable: true)]
    private ?string $unique;

    #[Filesystem\StoreAsPath(
        filesystem: 'public',
        namer: 'expression:files/{this.title|slug}-{checksum:7}{ext}',
        column: ['name' => 'custom_name'],
    )]
    private ?File $file1 = null;

    #[Filesystem\StoreAsPath(
        filesystem: 'public',
        namer: CustomObjectPathGenerator::class,
        deleteOnRemove: false,
        deleteOnUpdate: false,
    )]
    private ?Image $image1 = null;

    #[Filesystem\Stateless('public', namer: 'expression:{this.title|slug}.txt')]
    private File $virtualFile1;

    #[Filesystem\Stateless('public', namer: 'expression:{this.title|slug}.jpg')]
    private Image $virtualImage1;

    #[Filesystem\Stateless('public', namer: 'expression:some/dir/{this.title|slug}')]
    private Directory $virtualDir1;

    #[Filesystem\StoreAsDsn('public', namer: 'expression:files/{this.title|slug}-{checksum:7}{ext}')]
    private ?File $file2 = null;

    #[Filesystem\StoreAsDsn(
        filesystem: 'public',
        namer: CustomObjectPathGenerator::class,
        deleteOnRemove: false,
        deleteOnUpdate: false,
    )]
    private ?Image $image2 = null;

    #[Filesystem\StoreAsDsn(namer: 'expression:files/{this.title|slug}-{checksum:7}{ext}')]
    private ?File $file3 = null;

    #[Filesystem\StoreAsDsn(
        namer: CustomObjectPathGenerator::class,
        deleteOnRemove: false,
        deleteOnUpdate: false,
    )]
    private ?Image $image3 = null;

    #[Filesystem\StoreWithMetadata(
        metadata: [
            Metadata::PATH,
            Metadata::LAST_MODIFIED,
            Metadata::VISIBILITY,
            Metadata::MIME_TYPE,
            Metadata::SIZE,
            Metadata::CHECKSUM,
            Metadata::PUBLIC_URL,
        ],
        filesystem: 'public',
        namer: 'expression:files/{this.title|slug}-{checksum:7}{ext}'
    )]
    private ?File $file4 = null;

    #[Filesystem\StoreWithMetadata(
        metadata: [
            Metadata::DSN,
            Metadata::LAST_MODIFIED,
            Metadata::VISIBILITY,
            Metadata::MIME_TYPE,
            Metadata::SIZE,
            Metadata::CHECKSUM,
            Metadata::PUBLIC_URL,
            Metadata::TRANSFORM_URL => 'grayscale',
            Metadata::DIMENSIONS,
            Metadata::EXIF,
            Metadata::IPTC,
        ],
        filesystem: 'public',
        namer: 'expression:images/{this.title|slug}-{checksum:7}{ext}',
        deleteOnRemove: false,
        deleteOnUpdate: false,
    )]
    private ?Image $image4 = null;

    #[Filesystem\StoreWithMetadata(
        metadata: [
            Metadata::EXTENSION,
            Metadata::LAST_MODIFIED,
            Metadata::VISIBILITY,
            Metadata::MIME_TYPE,
            Metadata::SIZE,
            Metadata::CHECKSUM,
            Metadata::PUBLIC_URL,
        ],
        filesystem: 'public',
        namer: 'expression:files/{this.title|slug}-{checksum:7}{ext}'
    )]
    private ?File $file5 = null;

    #[Filesystem\StoreWithMetadata(
        metadata: [
            Metadata::EXTENSION,
            Metadata::LAST_MODIFIED,
            Metadata::VISIBILITY,
            Metadata::MIME_TYPE,
            Metadata::SIZE,
            Metadata::CHECKSUM,
            Metadata::PUBLIC_URL,
            Metadata::TRANSFORM_URL => 'grayscale',
            Metadata::DIMENSIONS,
            Metadata::EXIF,
            Metadata::IPTC,
        ],
        filesystem: 'public',
        namer: 'expression:images/{this.title|slug}-{checksum:7}{ext}',
        deleteOnRemove: false,
        deleteOnUpdate: false,
    )]
    private ?Image $image5 = null;

    public function __construct(string $title, ?string $unique = null)
    {
        $this->title = $title;
        $this->unique = $unique;
        $this->virtualFile1 = new PlaceholderFile();
        $this->virtualImage1 = new PlaceholderImage();
        $this->virtualDir1 = new PlaceholderDirectory();
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

    public function setUnique(string $unique): void
    {
        $this->unique = $unique;
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

    public function getVirtualDir1(): ?Directory
    {
        return $this->virtualDir1->exists() ? $this->virtualDir1 : null;
    }

    public function getFile2(): ?File
    {
        return $this->file2;
    }

    public function setFile2(?File $file2): void
    {
        $this->file2 = $file2;
    }

    public function getImage2(): ?Image
    {
        return $this->image2;
    }

    public function setImage2(?Image $image2): void
    {
        $this->image2 = $image2;
    }

    public function getFile3(): ?File
    {
        return $this->file3;
    }

    public function setFile3(?File $file3): void
    {
        $this->file3 = $file3;
    }

    public function getImage3(): ?Image
    {
        return $this->image3;
    }

    public function setImage3(?Image $image3): void
    {
        $this->image3 = $image3;
    }

    public function getFile4(): ?File
    {
        return $this->file4;
    }

    public function setFile4(?File $file4): void
    {
        $this->file4 = $file4;
    }

    public function getImage4(): ?Image
    {
        return $this->image4;
    }

    public function setImage4(?Image $image4): void
    {
        $this->image4 = $image4;
    }

    public function getFile5(): ?File
    {
        return $this->file5;
    }

    public function setFile5(?File $file5): void
    {
        $this->file5 = $file5;
    }

    public function getImage5(): ?Image
    {
        return $this->image5;
    }

    public function setImage5(?Image $image5): void
    {
        $this->image5 = $image5;
    }
}
