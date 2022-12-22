<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Node\File;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Node\File\PlaceholderFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class PlaceholderFileTest extends TestCase
{
    /**
     * @test
     */
    public function exists_is_always_false(): void
    {
        $file = $this->createFile();

        $this->assertFalse($file->exists());
    }

    /**
     * @test
     */
    public function any_other_method_results_in_error(): void
    {
        $file = $this->createFile();

        $this->expectException(\LogicException::class);

        $file->path();
    }

    protected function createFile(): PlaceholderFile
    {
        return new PlaceholderFile();
    }
}
