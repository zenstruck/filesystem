<?php

namespace Zenstruck\Filesystem\Tests\Bridge\Symfony\Bundle\Integration\Doctrine;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Filesystem\Node\File\FileCollection;
use Zenstruck\Filesystem\Node\File\LazyFileCollection;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;
use Zenstruck\Filesystem\Tests\FilesystemTest;
use Zenstruck\Filesystem\Tests\Fixture\Symfony\Factory\Entity1Factory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FileCollectionTypeTest extends KernelTestCase
{
    use Factories, InteractsWithFilesystem, ResetDatabase;

    /**
     * @test
     */
    public function entity_save_load_delete(): void
    {
        Entity1Factory::createOne([
            'collection' => new FileCollection([
                $this->filesystem()->write('file1.txt', 'content')->last(),
                $this->filesystem()->write('nested/file2.txt', 'content')->last(),
            ]),
        ]);

        $entity = Entity1Factory::last();

        $this->assertInstanceOf(LazyFileCollection::class, $entity->collection);
        $this->assertSame('file1.txt', $entity->collection->all()[0]->path());
        $this->assertSame('nested/file2.txt', $entity->collection->all()[1]->path());

        $this->filesystem()
            ->assertExists('file1.txt')
            ->assertExists('nested/file2.txt')
        ;

        $entity->remove();

        $this->filesystem()
            ->assertNotExists('file1.txt')
            ->assertNotExists('nested/file2.txt')
        ;
    }

    /**
     * @test
     */
    public function modify_collection_on_update(): void
    {
        $this->markTestIncomplete();

        $entity = Entity1Factory::createOne([
            'collection' => new FileCollection([
                $this->filesystem()->write('file1.txt', 'content')->last(),
                $this->filesystem()->write('nested/file2.txt', 'content')->last(),
            ]),
        ]);

        $this->filesystem()
            ->assertExists('file1.txt')
            ->assertExists('nested/file2.txt')
            ->assertNotExists('another/file3.txt')
        ;

        $entity->disableAutoRefresh();
        $entity->collection->add($this->filesystem()->write('another/file3.txt', 'content')->last());
        $entity->collection->remove($entity->collection->all()[0]);
        $entity->collection = new FileCollection($entity->collection->all());

        $entity->save();

        $this->filesystem()
            ->assertNotExists('file1.txt')
            ->assertExists('nested/file2.txt')
            ->assertExists('another/file3.txt')
        ;

        $entity = Entity1Factory::first();

        $this->assertCount(2, $entity->collection);
        $this->assertSame('file1.txt', $entity->collection->all()[0]->path());
        $this->assertSame('another/file3.txt', $entity->collection->all()[1]->path());
    }

    /**
     * @test
     */
    public function pending_files_on_persist(): void
    {
        Entity1Factory::createOne([
            'collection' => new FileCollection([
                new PendingFile(FilesystemTest::FIXTURE_DIR.'/symfony.png'),
                $this->filesystem()->write('file1.txt', 'content')->last()->ensureFile(),
            ]),
        ]);

        $this->filesystem()->assertExists('symfony.png');
        $this->filesystem()->assertExists('file1.txt');
        $this->assertSame('image/png', Entity1Factory::first()->collection->all()[0]->mimeType());
        $this->assertSame('text/plain', Entity1Factory::first()->collection->all()[1]->mimeType());
    }

    /**
     * @test
     */
    public function pending_files_on_update(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function delete_collection(): void
    {
        $entity = Entity1Factory::createOne([
            'collection' => new FileCollection([
                $this->filesystem()->write('file1.txt', 'content')->last(),
                $this->filesystem()->write('nested/file2.txt', 'content')->last(),
            ]),
        ]);

        $this->filesystem()
            ->assertExists('file1.txt')
            ->assertExists('nested/file2.txt')
        ;

        $entity->collection = null;
        $entity->save();

        $this->assertNull(Entity1Factory::first()->collection);

        $this->filesystem()
            ->assertNotExists('file1.txt')
            ->assertNotExists('nested/file2.txt')
        ;
    }
}
