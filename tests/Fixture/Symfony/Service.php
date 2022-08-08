<?php

namespace Zenstruck\Filesystem\Tests\Fixture\Symfony;

use Zenstruck\Filesystem;

final class Service
{
    public function __construct(
        public Filesystem $general,
        public Filesystem $publicFilesystem,
        public Filesystem $privateFilesystem,
    ) {
    }
}
