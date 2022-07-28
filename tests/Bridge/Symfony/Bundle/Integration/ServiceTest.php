<?php

namespace Zenstruck\Filesystem\Tests\Bridge\Symfony\Bundle\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Filesystem\Tests\Fixture\Symfony\Service;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ServiceTest extends KernelTestCase
{
    /**
     * @test
     */
    public function can_get_named_filesystems(): void
    {
        /** @var Service $service */
        $service = self::getContainer()->get(Service::class);

        $this->assertInstanceOf(MultiFilesystem::class, $service->general);
        $this->assertSame('public', $service->general->name());
        $this->assertSame('public', $service->public->name());
        $this->assertSame('private', $service->private->name());
    }
}
