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

use Zenstruck\Filesystem\Node\File\Image\PendingImage;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Tests\Filesystem\Doctrine\DoctrineTestCase;

use function Zenstruck\Foundry\repository;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class NodeLifecycleListenerTest extends DoctrineTestCase
{
    /**
     * @test
     */
    public function files_deleted_on_remove(): void
    {
        $class = $this->entityClass();
        $object = new $class('foo');
        $object->setFile1($this->filesystem()->write('some/file.txt', 'content1')->last());
        $object->setImage1($this->filesystem()->write('some/image.png', 'content2')->last()->ensureImage());

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
     */
    public function files_deleted_on_change(): void
    {
        $class = $this->entityClass();
        $object = new $class('foo');
        $this->em()->persist($object);
        $this->flushAndAssertNoChangesFor($object);

        $object->setFile1($this->filesystem()->write('some/file.txt', 'content1')->last());
        $object->setImage1($this->filesystem()->write('some/image.png', 'content2')->last()->ensureImage());

        $this->flushAndAssertNoChangesFor($object);
        $this->em()->clear();

        $this->filesystem()->assertExists('some/file.txt');
        $this->filesystem()->assertExists('some/image.png');

        $fromDb = repository($class)->first()->object();

        $fromDb->setFile1($this->filesystem()->write('some/new-file.txt', 'content3')->last());
        $object->setImage1($this->filesystem()->write('some/new-image.png', 'content4')->last()->ensureImage());

        $this->flushAndAssertNoChangesFor($object);

        $this->filesystem()->assertExists('some/new-file.txt');
        $this->filesystem()->assertExists('some/new-image.png');
        $this->filesystem()->assertNotExists('some/file.txt');
        $this->filesystem()->assertExists('some/image.png');
    }

    /**
     * @test
     */
    public function files_deleted_on_set_null(): void
    {
        $class = $this->entityClass();
        $object = new $class('foo');
        $object->setFile1($this->filesystem()->write('some/file.txt', 'content1')->last());
        $object->setImage1($this->filesystem()->write('some/image.png', 'content2')->last()->ensureImage());

        $this->em()->persist($object);
        $this->flushAndAssertNoChangesFor($object);
        $this->em()->clear();

        $this->filesystem()->assertExists('some/file.txt');
        $this->filesystem()->assertExists('some/image.png');

        $fromDb = repository($class)->first()->object();

        $fromDb->setFile1(null);
        $object->setImage1(null);

        $this->flushAndAssertNoChangesFor($object);

        $this->filesystem()->assertNotExists('some/file.txt');
        $this->filesystem()->assertExists('some/image.png');
    }

    /**
     * @test
     */
    public function file_not_deleted_if_path_the_same(): void
    {
        $class = $this->entityClass();
        $object = new $class('foo');
        $object->setFile1($this->filesystem()->write('some/file.txt', 'content1')->last());

        $this->em()->persist($object);
        $this->flushAndAssertNoChangesFor($object);

        $this->assertSame('content1', $object->getFile1()->contents());

        $object->setFile1($this->filesystem()->write('some/file.txt', 'new content')->last());

        $this->flushAndAssertNoChangesFor($object);
        $this->em()->clear();

        $fromDb = repository($class)->first()->object();

        $this->assertSame('new content', $this->loadMappingFor($fromDb)->getFile1()->contents());
    }

    /**
     * @test
     */
    public function can_persist_and_update_pending_files(): void
    {
        $class = $this->entityClass();
        $object = new $class('Foo');
        $object->setFile1(new PendingFile(fixture('sub1/file1.txt')));
        $object->setImage1(new PendingImage(fixture('symfony.jpg')));

        $this->em()->persist($object);
        $this->flushAndAssertNoChangesFor($object);

        $this->filesystem()->assertSame('files/foo-d41d8cd.txt', 'fixture://sub1/file1.txt');
        $this->filesystem()->assertSame('images/foo-42890a2.jpg', 'fixture://symfony.jpg');
        $this->assertSame('files/foo-d41d8cd.txt', $object->getFile1()->path()->toString());
        $this->assertSame('d41d8cd98f00b204e9800998ecf8427e', $object->getFile1()->checksum());
        $this->assertSame('images/foo-42890a2.jpg', $object->getImage1()->path()->toString());
        $this->assertSame('42890a25562a1803949caa09d235f242', $object->getImage1()->checksum());

        $object->setFile1(new PendingFile(fixture('archive.zip')));
        $object->setImage1(new PendingImage(fixture('symfony.png')));

        $this->flushAndAssertNoChangesFor($object);

        $this->filesystem()->assertNotExists('files/foo-d41d8cd.txt');
        $this->filesystem()->assertExists('images/foo-42890a2.jpg');
        $this->filesystem()->assertSame('files/foo-0a4a9b1.zip', 'fixture://archive.zip');
        $this->filesystem()->assertSame('images/foo-ac6884f.png', 'fixture://symfony.png');
        $this->assertSame('files/foo-0a4a9b1.zip', $object->getFile1()->path()->toString());
        $this->assertSame('0a4a9b1c162b2b4ccfa9db645f8b7eaa', $object->getFile1()->checksum());
        $this->assertSame('images/foo-ac6884f.png', $object->getImage1()->path()->toString());
        $this->assertSame('ac6884fc84724d792649552e7211843a', $object->getImage1()->checksum());

        $this->em()->clear();

        $this->assertSame('files/foo-0a4a9b1.zip', $object->getFile1()->path()->toString());
        $this->assertSame('0a4a9b1c162b2b4ccfa9db645f8b7eaa', $object->getFile1()->checksum());
        $this->assertSame('images/foo-ac6884f.png', $object->getImage1()->path()->toString());
        $this->assertSame('ac6884fc84724d792649552e7211843a', $object->getImage1()->checksum());
    }

    /**
     * @return class-string
     */
    abstract protected function entityClass(): string;
}
