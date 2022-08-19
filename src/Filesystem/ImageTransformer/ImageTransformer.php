<?php

namespace Zenstruck\Filesystem\ImageTransformer;

use BadMethodCallException;
use Closure;
use League\Flysystem\FilesystemException;
use Zenstruck\Filesystem\Adapter\Operator;
use Zenstruck\Filesystem\ImageTransformer\Driver\GDDriver;
use Zenstruck\Filesystem\ImageTransformer\Driver\ImagickDriver;
use Zenstruck\Filesystem\ImageTransformer\Driver\InterventionDriver;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
final class ImageTransformer
{
    private ?Driver $driver = null;
    private ?Closure $closure = null;

    public function __construct(
        private Image $image,
        private Operator $operator
    ) {
    }

    public function usingGD(callable $callable): self
    {
        $this->ensureUnset();

        $this->driver = new GDDriver();
        $this->closure = Closure::fromCallable($callable);

        return $this;
    }

    public function usingImagick(callable $callable): self
    {
        $this->ensureUnset();

        $this->driver = new ImagickDriver();
        $this->closure = Closure::fromCallable($callable);

        return $this;
    }

    public function usingIntervention(callable $callable): self
    {
        $this->ensureUnset();

        $this->driver = new InterventionDriver();
        $this->closure = Closure::fromCallable($callable);

        return $this;
    }

    /**
     * @throws FilesystemException
     */
    public function overwrite(): Image
    {
        $resource = $this->transform();
        $this->write($this->image->path(), $resource);

        return $this->image->refresh();
    }

    /**
     * @throws FilesystemException
     */
    public function saveAs(string $path): Image
    {
        $resource = $this->transform();
        assert($this->driver instanceof Driver);

        $this->write($path, $resource);

        // TODO: Create new Image object for the new path
        return $this->image->refresh();
    }

    /**
     * @throws BadMethodCallException
     */
    private function ensureUnset(): void
    {
        if (null !== $this->driver || null !== $this->closure) {
            throw new BadMethodCallException("Transformation is already configured");
        }
    }

    /**
     * @throws BadMethodCallException
     */
    private function ensureSet(): void
    {
        if (null === $this->driver || null === $this->closure) {
            throw new BadMethodCallException("Transformation is not configured");
        }
    }

    private function transform(): mixed
    {
        $this->ensureSet();
        assert($this->closure instanceof Closure);
        assert($this->driver instanceof Driver);
        assert($this->operator instanceof Operator);

        $input = $this->driver->loadFromImage($this->image);
        return ($this->closure)($input);
    }

    /**
     * @throws FilesystemException
     */
    private function write(string $path, mixed $resource): void
    {
        assert($this->driver instanceof Driver);

        if (is_string($resource)) {
            $this->operator->write(
                $path,
                $resource
            );
        } else {
            $this->operator->writeStream(
                $path,
                $resource
            );
        }
    }
}
