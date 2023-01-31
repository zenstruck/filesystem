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
use Zenstruck\Filesystem\Node\Mapping as Filesystem;

#[ORM\Entity]
#[Filesystem\HasFiles(autoload: false)]
class Entity2 extends Entity1
{
}
