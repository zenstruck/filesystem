<?php

namespace Zenstruck\Filesystem\Tests\Fixture\Symfony;

use Zenstruck\Filesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Service
{
    public function __construct(
        public Filesystem $general,
        public Filesystem $public,
        public Filesystem $private,
    ) {
    }
}
