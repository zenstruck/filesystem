<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Doctrine\EventListener;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\Image\PendingImage;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Tests\Filesystem\Doctrine\DoctrineTestCase;

use function Zenstruck\Foundry\repository;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class NodeLifecycleListenerTest extends DoctrineTestCase
{
    public static function fileMethodProvider(): iterable
    {
        yield 'path' => [1];
        yield 'dsn' => [2];
        yield 'metadata' => [4];
    }

    public static function fileMetadataMethodProvider(): iterable
    {
        yield from self::fileMethodProvider();

        yield 'metadata_without_path' => [5];
    }

    /**
     * @test
     * @dataProvider fileMethodProvider
     */
    public function files_deleted_on_remove(int $num): void
    {
        $class = $this->entityClass();
        $object = new $class('foo');
        $object->{'setFile'.$num}($this->filesystem()->write('some/file.txt', 'content1'));
        $object->{'setImage'.$num}($this->filesystem()->write('some/image.png', fixture('metadata.jpg'))->ensureImage());

        $this->em()->persist($object);
        $this->flushAndAssertNoChangesFor($object);
        $this->em()->clear();

        $this->filesystem()->assertExists('some/file.txt');
        $this->filesystem()->assertExists('some/image.png');

        $fromDb = repository($class)->first()->object();

        $this->em()->remove($fromDb);
        $this->em()->flush();

        repository($class)->assert()->count(0);

        $this->filesystem()->assertNotExists('some/file.txt');
        $this->filesystem()->assertExists('some/image.png');
    }

    /**
     * @test
     * @dataProvider fileMethodProvider
     */
    public function files_deleted_on_change(int $num): void
    {
        $class = $this->entityClass();
        $object = new $class('foo');
        $this->em()->persist($object);
        $this->flushAndAssertNoChangesFor($object);

        $object->{'setFile'.$num}($this->filesystem()->write('some/file.txt', 'content1'));
        $object->{'setImage'.$num}($this->filesystem()->write('some/image.png', fixture('metadata.jpg'))->ensureImage());

        $this->flushAndAssertNoChangesFor($object);
        $this->em()->clear();

        $this->filesystem()->assertExists('some/file.txt');
        $this->filesystem()->assertExists('some/image.png');

        $fromDb = repository($class)->first()->object();

        $fromDb->{'setFile'.$num}($this->filesystem()->write('some/new-file.txt', 'content3'));
        $object->{'setImage'.$num}($this->filesystem()->write('some/new-image.png', fixture('metadata.jpg'))->ensureImage());

        $this->flushAndAssertNoChangesFor($object);

        $this->filesystem()->assertExists('some/new-file.txt');
        $this->filesystem()->assertExists('some/new-image.png');
        $this->filesystem()->assertNotExists('some/file.txt');
        $this->filesystem()->assertExists('some/image.png');
    }

    /**
     * @test
     * @dataProvider fileMethodProvider
     */
    public function files_deleted_on_set_null(int $num): void
    {
        $class = $this->entityClass();
        $object = new $class('foo');
        $object->{'setFile'.$num}($this->filesystem()->write('some/file.txt', 'content1'));
        $object->{'setImage'.$num}($this->filesystem()->write('some/image.png', fixture('metadata.jpg'))->ensureImage());

        $this->em()->persist($object);
        $this->flushAndAssertNoChangesFor($object);
        $this->em()->clear();

        $this->filesystem()->assertExists('some/file.txt');
        $this->filesystem()->assertExists('some/image.png');

        $fromDb = repository($class)->first()->object();

        $fromDb->{'setFile'.$num}(null);
        $object->{'setImage'.$num}(null);

        $this->flushAndAssertNoChangesFor($object);

        $this->filesystem()->assertNotExists('some/file.txt');
        $this->filesystem()->assertExists('some/image.png');
    }

    /**
     * @test
     * @dataProvider fileMethodProvider
     */
    public function files_stored_as_path_not_deleted_if_path_the_same(int $num): void
    {
        $class = $this->entityClass();
        $object = new $class('foo');
        $object->{'setFile'.$num}($this->filesystem()->write('some/file.txt', 'content1'));

        $this->em()->persist($object);
        $this->flushAndAssertNoChangesFor($object);

        $this->assertSame('content1', $object->{'getFile'.$num}()->contents());

        $object->{'setFile'.$num}($this->filesystem()->write('some/file.txt', 'new content'));

        $this->flushAndAssertNoChangesFor($object);
        $this->em()->clear();

        $fromDb = repository($class)->first()->object();

        $this->assertSame('new content', $this->loadMappingFor($fromDb)->{'getFile'.$num}()->contents());
    }

    /**
     * @test
     * @dataProvider fileMetadataMethodProvider
     */
    public function can_persist_and_update_pending_files_stored_as_path(int $num): void
    {
        $class = $this->entityClass();
        $object = new $class('Foo');
        $object->{'setFile'.$num}(new PendingFile(fixture('sub1/file1.txt')));
        $object->{'setImage'.$num}(new PendingImage(fixture('symfony.jpg')));

        $this->em()->persist($object);
        $this->flushAndAssertNoChangesFor($object);

        $this->filesystem()->assertSame('files/foo-d41d8cd.txt', 'fixture://sub1/file1.txt');
        $this->filesystem()->assertSame('images/foo-42890a2.jpg', 'fixture://symfony.jpg');
        $this->assertSame('files/foo-d41d8cd.txt', $object->{'getFile'.$num}()->path()->toString());
        $this->assertSame('d41d8cd98f00b204e9800998ecf8427e', $object->{'getFile'.$num}()->checksum());
        $this->assertSame('images/foo-42890a2.jpg', $object->{'getImage'.$num}()->path()->toString());
        $this->assertSame('42890a25562a1803949caa09d235f242', $object->{'getImage'.$num}()->checksum());

        $object->{'setFile'.$num}(new PendingFile(fixture('archive.zip')));
        $object->{'setImage'.$num}(new PendingImage(fixture('symfony.png')));

        $this->flushAndAssertNoChangesFor($object);

        $this->filesystem()->assertNotExists('files/foo-d41d8cd.txt');
        $this->filesystem()->assertExists('images/foo-42890a2.jpg');
        $this->filesystem()->assertSame('files/foo-0a4a9b1.zip', 'fixture://archive.zip');
        $this->filesystem()->assertSame('images/foo-ac6884f.png', 'fixture://symfony.png');
        $this->assertSame('files/foo-0a4a9b1.zip', $object->{'getFile'.$num}()->path()->toString());
        $this->assertSame('0a4a9b1c162b2b4ccfa9db645f8b7eaa', $object->{'getFile'.$num}()->checksum());
        $this->assertSame('images/foo-ac6884f.png', $object->{'getImage'.$num}()->path()->toString());
        $this->assertSame('ac6884fc84724d792649552e7211843a', $object->{'getImage'.$num}()->checksum());

        $this->em()->clear();

        $this->assertSame('files/foo-0a4a9b1.zip', $object->{'getFile'.$num}()->path()->toString());
        $this->assertSame('0a4a9b1c162b2b4ccfa9db645f8b7eaa', $object->{'getFile'.$num}()->checksum());
        $this->assertSame('images/foo-ac6884f.png', $object->{'getImage'.$num}()->path()->toString());
        $this->assertSame('ac6884fc84724d792649552e7211843a', $object->{'getImage'.$num}()->checksum());
    }

    /**
     * @test
     * @dataProvider fileMethodProvider
     */
    public function persisting_failure_does_not_write_pending_file(int $num): void
    {
        $class = $this->entityClass();
        $object1 = new $class('Foo', 'bar');

        $this->em()->persist($object1);
        $this->em()->flush();

        $object2 = new $class('Foo', 'bar');
        $object2->{'setFile'.$num}(new PendingFile(fixture('sub1/file1.txt')));

        $this->filesystem()->directory()->recursive()->files()->assertCount(0);

        $this->em()->persist($object2);

        try {
            $this->em()->flush();
        } catch (UniqueConstraintViolationException) {
            $this->filesystem()->directory()->recursive()->files()->assertCount(0);

            return;
        }

        $this->fail('assertion not caught');
    }

    /**
     * @test
     * @dataProvider fileMethodProvider
     */
    public function updating_failure_does_not_write_pending_file(int $num): void
    {
        $class = $this->entityClass();
        $object1 = new $class('Foo', 'bar');
        $object2 = new $class('Bar', 'baz');

        $this->em()->persist($object1);
        $this->em()->persist($object2);
        $this->em()->flush();

        $this->filesystem()->directory()->recursive()->files()->assertCount(0);

        $object2->{'setFile'.$num}(new PendingFile(fixture('sub1/file1.txt')));
        $object2->setUnique('bar');

        try {
            $this->em()->flush();
        } catch (UniqueConstraintViolationException) {
            $this->filesystem()->directory()->recursive()->files()->assertCount(0);

            return;
        }

        $this->fail('assertion not caught');
    }

    /**
     * @test
     */
    public function metadata_is_pulled_from_attributes(): void
    {
        $class = $this->entityClass();
        $object = new $class('Foo');
        $object->setImage4($this->filesystem()->write('some/image.png', fixture('symfony.png'))->ensureImage());

        $this->em()->persist($object);
        $this->flushAndAssertNoChangesFor($object);
        $this->em()->clear();

        $this->filesystem()->delete('some/image.png');

        $fromDb = repository($class)->first()->object();

        /** @var Image $image */
        $image = $fromDb->getImage4();

        $this->assertSame('public://some/image.png', $image->dsn()->toString());
        $this->assertSame('some/image.png', $image->path()->toString());
        $this->assertSame('image/png', $image->mimeType());
        $this->assertInstanceOf(\DateTimeInterface::class, $image->lastModified());
        $this->assertSame('public', $image->visibility());
        $this->assertSame(10862, $image->size());
        $this->assertSame('ac6884fc84724d792649552e7211843a', $image->checksum());
        $this->assertSame('/prefix/some/image.png', $image->publicUrl());
        $this->assertSame('http://localhost/transform/some/image.png?filter=grayscale', $image->transformUrl('grayscale'));
        $this->assertSame(563, $image->dimensions()->width());
        $this->assertSame(678, $image->dimensions()->height());
        $this->assertSame([], $image->exif());
        $this->assertSame([], $image->iptc());

        $this->loadMappingFor($fromDb);
        $this->assertFalse($image->exists());
    }

    /**
     * @test
     */
    public function filesystem_is_required_when_saving_to_pending_file_using_dsn(): void
    {
        $class = $this->entityClass();
        $object = new $class('Foo');
        $object->setFile3(new PendingFile(fixture('sub1/file1.txt')));

        $this->expectException(\LogicException::class);

        $this->em()->persist($object);
    }

    /**
     * @test
     */
    public function persist_and_update_file_with_different_filesystem(): void
    {
        $file1 = $this->filesystem()->write('private://file1.txt', 'content 1');
        $file2 = $this->filesystem()->write('private://file2.txt', 'content 2');
        $class = $this->entityClass();
        $object = new $class('Foo');
        $object->setFile1($file1);

        $this->em()->persist($object);
        $this->em()->flush();
        $this->em()->clear();

        $fromDb = repository($class)->first()->object();
        $this->loadMappingFor($fromDb);

        $file = $fromDb->getFile1();

        $this->assertTrue($file->exists());
        $this->assertSame('public', $file->dsn()->filesystem());
        $this->assertSame('files/foo-9297ab3.txt', $file->path()->toString());
        $this->filesystem()->assertExists('files/foo-9297ab3.txt');

        $fromDb->setFile1($file2);
        $this->em()->flush();
        $this->em()->clear();

        $fromDb = repository($class)->first()->object();
        $this->loadMappingFor($fromDb);

        $file = $fromDb->getFile1();

        $this->assertTrue($file->exists());
        $this->assertSame('public', $file->dsn()->filesystem());
        $this->assertSame('files/foo-6685cd6.txt', $file->path()->toString());
        $this->filesystem()->assertNotExists('files/foo-9297ab3.txt');
    }

    /**
     * @return class-string
     */
    abstract protected function entityClass(): string;
}
