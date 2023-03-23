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

use Zenstruck\Filesystem\Node\File\PlaceholderFile;
use Zenstruck\Tests\Fixtures\Entity\Entity1;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class QueryTest extends DoctrineTestCase
{
    /**
     * @test
     */
    public function can_query_store_as_path_column(): void
    {
        $object = new Entity1('FoO');
        $object->setFile1($this->filesystem()->write('private://some/file1.txt', 'content1'));

        $this->em()->persist($object);
        $this->em()->flush();

        $this->assertNotNull($this->em()->getRepository(Entity1::class)->findOneBy(['file1' => new PlaceholderFile('files/foo-7e55db0.txt')]));
    }
}
