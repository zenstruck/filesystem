<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Doctrine;

use Zenstruck\Filesystem\Doctrine\FileMappingLoader;
use Zenstruck\Filesystem\Node\Directory\LazyDirectory;
use Zenstruck\Filesystem\Node\File\Image\LazyImage;
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Tests\Fixtures\Entity\Entity2;

use function Zenstruck\Foundry\repository;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FileMappingLoaderTest extends DoctrineTestCase
{
    /**
     * @test
     */
    public function can_load_files_for_object(): void
    {
        $object = new Entity2('FoO');
        $object->setFile1($this->filesystem()->write('some/file1.txt', 'content1')->ensureFile());
        $object->setImage1($this->filesystem()->write('some/image1.png', 'content2')->ensureImage());
        $object->setFile2($this->filesystem()->write('some/file2.txt', 'content3')->ensureFile());
        $object->setImage2($this->filesystem()->write('some/image2.png', 'content4')->ensureImage());
        $object->setFile3($this->filesystem()->write('some/file3.txt', 'content5')->ensureFile());
        $object->setImage3($this->filesystem()->write('some/image3.png', 'content6')->ensureImage());
        $object->setFile4($this->filesystem()->write('some/file4.txt', 'content7')->ensureFile());
        $object->setImage4($this->filesystem()->write('some/image4.png', fixture('metadata.jpg'))->ensureImage());

        $this->filesystem()->write('foo.txt', 'content20');
        $this->filesystem()->write('foo.jpg', 'content21');
        $this->filesystem()->write('some/dir/foo/file1.txt', 'content22');
        $this->filesystem()->write('some/dir/foo/file2.txt', 'content23');

        $this->em()->persist($object);
        $this->flushAndAssertNoChangesFor($object);
        $this->em()->clear();

        $fromDb = repository(Entity2::class)->first()->object();
        $fromDb = self::getContainer()->get(FileMappingLoader::class)($fromDb);
        $fromDb = self::getContainer()->get(FileMappingLoader::class)($fromDb); // ensure multiple calls work
        self::getContainer()->get(FileMappingLoader::class)([$fromDb]);

        $this->assertInstanceOf(LazyFile::class, $fromDb->getFile1());
        $this->assertSame('content1', $fromDb->getFile1()->contents());
        $this->assertInstanceOf(LazyImage::class, $fromDb->getImage1());
        $this->assertSame('content2', $fromDb->getImage1()->contents());
        $this->assertInstanceOf(LazyFile::class, $fromDb->getFile2());
        $this->assertSame('content3', $fromDb->getFile2()->contents());
        $this->assertInstanceOf(LazyImage::class, $fromDb->getImage2());
        $this->assertSame('content4', $fromDb->getImage2()->contents());
        $this->assertInstanceOf(LazyFile::class, $fromDb->getFile3());
        $this->assertSame('content5', $fromDb->getFile3()->contents());
        $this->assertInstanceOf(LazyImage::class, $fromDb->getImage3());
        $this->assertSame('content6', $fromDb->getImage3()->contents());
        $this->assertInstanceOf(LazyFile::class, $fromDb->getFile4());
        $this->assertSame('content7', $fromDb->getFile4()->contents());
        $this->assertInstanceOf(LazyImage::class, $fromDb->getImage4());
        $this->assertSame(\file_get_contents(fixture('metadata.jpg')), $fromDb->getImage4()->contents());

        $this->assertInstanceOf(LazyFile::class, $fromDb->getVirtualFile1());
        $this->assertSame('content20', $fromDb->getVirtualFile1()->contents());
        $this->assertInstanceOf(LazyImage::class, $fromDb->getVirtualImage1());
        $this->assertSame('content21', $fromDb->getVirtualImage1()->contents());
        $this->assertInstanceOf(LazyDirectory::class, $fromDb->getVirtualDir1());
        $this->assertCount(2, $fromDb->getVirtualDir1());

        $files = \iterator_to_array($fromDb->getVirtualDir1());

        $this->assertTrue($files[0]->exists());
        $this->assertTrue($files[1]->exists());
        $this->assertSame('some/dir/foo/file1.txt', $files[0]->path()->toString());
        $this->assertSame('some/dir/foo/file2.txt', $files[1]->path()->toString());
    }
}
