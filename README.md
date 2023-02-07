# zenstruck/filesystem

This library is a wrapper for the excellent [league/flysystem](https://flysystem.thephpleague.com/docs/)
_File Storage Abstraction_ library. It provides an _alternate_ [API](#api) with the following major
changes:

1. The main difference is the concept of [`Directory`](#directory), [`File`](#file) and [`Image`](#image) objects.
   These are wrappers for an individual _filesystem node_ and provide info, metadata and more features. These can
   be passed around (ie sent to your templates) or even used as [Doctrine Types](#doctrine-integration).
2. Combine certain Flysystem methods. For example, `delete()` removes both files and directories, `write()` can
   write both strings and streams (+ more).
3. Eases the use of filesystem files as _real, local files_. Many 3rd party libraries that manipulate files
   require local files.

Additionally, the following features are provided:

1. Filesystem _wrappers_ to add additional functionality (ie [`MultiFilesystem`](#multifilesystem),
   and [`LoggableFilesystem`](#loggablefilesystem)).
2. Powerful [testing helpers](#testing).
3. [`ArchiveFile`](#archivefile) representing a local zip file that acts as both a filesystem _and_ a real file.
4. [Doctrine Integration](#doctrine-integration).
5. [Symfony Integration](#symfony-integration)
    - [Custom Responses](#responses)
    - [Validators](#validators)
    - [Bundle](#bundle) to help configure filesystem services, wire the Doctrine integration and additional
      testing helpers.

## Installation

```bash
composer require zenstruck/filesystem
```

## API

### `Filesystem`

```php
/** @var \Zenstruck\Filesystem $filesystem */

// read operations
$filesystem->has('some/path'); // bool
$filesystem->node('some/path'); // Zenstruck\Filesystem\Node or throws NodeNotFound
$filesystem->file('some/path.txt'); // Zenstruck\Filesystem\Node\File or throws NodeNotFound or NodeTypeMismatch (if exists but not a file)
$filesystem->image('some/path.png'); // Zenstruck\Filesystem\Node\File\Image or throws NodeNotFound or NodeTypeMismatch (if exists but not an image)
$filesystem->directory('some/path'); // Zenstruck\Filesystem\Node\Directory or throws NodeNotFound or NodeTypeMismatch (if exists but not a directory)

// write operations
$filesystem->write('some/path.txt', 'string contents'); // write a string
$filesystem->write('some/path.txt', $resource); // write a resource
$filesystem->write('some/path.txt', new \SplFileInfo('path/to/local/file.txt')); // write a local file
$filesystem->write('some/prefix', new \SplFileInfo('path/to/local/directory')); // write a local directory
$filesystem->write('some/path.txt', $file); // write a Zenstruck\Filesystem\Node\File
$filesystem->write('some/prefix', $directory); // write a Zenstruck\Filesystem\Node\Directory

$filesystem->copy('from/file.txt', 'dest/file.txt');

$filesystem->move('from/file.txt', 'dest/file.txt');

$filesystem->delete('some/file.txt');
$filesystem->delete('some/directory');

$filesystem->mkdir('some/directory');

$filesystem->chmod('some/file.txt', 'private');

// utility methods
$filesystem->name(); // string - human-readable name for the filesystem

$filesystem->last(); // Zenstruck\Filesystem\Node the node of the last write operation
```

### `Node`

Interface: `Zenstruck\Filesystem\Node`.

```php
/** @var \Zenstruck\Filesystem\Node $node */

$node->path(); // Zenstruck\Filesystem\Node\Path
$node->path()->toString(); // string - the full path
(string) $node->path(); // same as above
$node->path()->name(); // string - filename with extension
$node->path()->basename(); // string - filename without extension
$node->path()->extension(); // string|null - file extension
$node->path()->dirname(); // string - the parent directory

$node->dsn(); // Zenstruck\Filesystem\Node\Dsn
$node->dsn()->toString(); // string - <filesystem-name>://<full-path>
(string) $node->dsn(); // same as above
$node->dsn()->path(); // Zenstruck\Filesystem\Node\Path
$node->dsn()->filesystem(); // string - name of the filesystem this node belongs to

$node->directory(); // Zenstruck\Filesystem\Node\Directory|null - parent directory object

$node->visibility(); // string - ie "public" or "private"
$node->lastModified(); // \DateTimeImmutable (in currently configured timezone)

$node->exists(); // bool
$node->ensureExists(); // static or throws NodeNotFound

$node->refresh(); // static and clears any cached metadata

$node->ensureDirectory(); // Zenstruck\Filesystem\Node\Directory or throws NodeTypeMismatch (if not a directory)
$node->ensureFile(); // Zenstruck\Filesystem\Node\File or throws NodeTypeMismatch (if not a file)
$node->ensureImage(); // Zenstruck\Filesystem\Node\Image or throws NodeTypeMismatch (if not an image)
```

### `File`

Interface: `Zenstruck\Filesystem\Node\File` (extends [`Node`](#node)).

```php
/** @var \Zenstruck\Filesystem\Node\File $file */

$file->contents(); // string - the file's contents

$file->read(); // resource

$file->size(); // int

$file->guessExtension(); // string|null - returns extension if available or attempts to guess from mime-type

$file->checksum(); // string - using FilesystemAdapter's default algorithm
$file->checksum('md5'); // string - specify the algorithm

$file->publicUrl(); // string (needs to be configured)
$file->temporaryUrl(new \DateTimeImmutable('+30 minutes')); // string - expiring url (needs to be configured)
$file->temporaryUrl('+30 minutes'); // equivalent to above

$file->tempFile(); // \SplFileInfo - temporary local file that's deleted at the end of the script
```

> **Note**: See [`zenstruck/temp-file`](https://github.com/zenstruck/temp-file#zenstrucktemp-file) for more
> details about `File::tempFile()`.

#### `PendingFile`

Class: `Zenstruck\Filesystem\Node\File\PendingFile` (extends `\SplFileInfo` and implements [`File`](#file)).

```php
use Zenstruck\Filesystem\Node\File\PendingFile;

$file = new PendingFile('/path/to/local/file.txt');
$file->path()->toString(); // "/path/to/local/file.txt"

/** @var \Symfony\Component\HttpFoundation\File\UploadedFile $uploadedFile */

$file = new PendingFile($uploadedFile);
$file->path()->toString(); // $uploadedFile->getClientOriginalName()

/** @var \Psr\Http\Message\UploadedFileInterface $uploadedFile */

$file = new PendingFile($uploadedFile);
$file->path()->toString(); // $uploadedFile->getClientFilename()
```

#### `Image`

Interface: `Zenstruck\Filesystem\Node\File\Image` (extends [`File`](#file)).

```php
/** @var \Zenstruck\Filesystem\Node\File\Image $image */

$image->dimensions(); // Zenstruck\Image\Dimensions
$image->dimensions()->height(); // int
$image->dimensions()->width(); // int
$image->dimensions()->pixels(); // int
$image->dimensions()->aspectRatio(); // float
$image->dimensions()->isSquare(); // bool
$image->dimensions()->isPortrait(); // bool
$image->dimensions()->isLandscape(); // bool

$image->exif(); // array - image exif data if available
$image->iptc(); // array - image iptc data if available

$image->transformUrl('filter-name'); // string (needs to be configured)
$image->transformUrl(['w' => 100, 'h' => 50]); // string (needs to be configured)

$image->transform(
    function(ManipulationObject $image) {
        // make manipulations

        return $image;
    }
); // PendingImage
```

> **Note**: See [`zenstruck/image`](https://github.com/zenstruck/image#zenstruckimage) for more
> details about `Image::transform()`.

##### `PendingImage`

Class: `Zenstruck\Filesystem\Node\File\Image\PendingImage` (extends [`PendingFile`](#pendingfile) and implements [`Image`](#image)).

```php
use Zenstruck\Filesystem\Node\File\Image\PendingImage;

$image = new PendingImage('/path/to/local/file.txt');
$image = new PendingImage($symfonyUploadedFile);

// transform and overwrite
$image->transformInPlace(
    function(ManipulationObject $image) {
        // make manipulations

        return $image;
    }
); // self
```

### `Directory`

Interface: `Zenstruck\Filesystem\Node\Directory` (extends [`Node`](#node)).

```php
/** @var Zenstruck\Filesystem\Node\Directory $directory */

// iterate over nodes (non-recursive)
foreach ($directory as $node) {
    /** @var Zenstruck\Filesystem\Node $node */
}

// iterate over only files (non-recursive)
foreach ($directory->files() as $file) {
    /** @var Zenstruck\Filesystem\Node\File $file */
}

// iterate over only directories (non-recursive)
foreach ($directory->directories() as $dir) {
    /** @var Zenstruck\Filesystem\Node\Directory $dir */
}

// recursively iterate
foreach ($directory->recursive() as $node) {
    /** @var Zenstruck\Filesystem\Node $node */
}

// advanced filter
$directories = $directory
    ->recursive()
    ->files()
    ->largerThan('10M')
    ->smallerThan('1G')
    ->olderThan('30 days ago')
    ->newerThan('20 days ago')
    ->matchingFilename('*.twig')
    ->notMatchingFilename('*.txt.twig')
    ->matchingPath('/files/')
    ->notMatchingPath('/exclude/')
    ->filter(function(File $file) { // custom filter
        if ($someCondition) {
            return false; // exclude
        }

        return true; // include
    })
;
```

> **Note**: Most of the _advanced filters_ require `symfony/finder` (`composer require symfony/finder`).

## Filesystems

### `FlysystemFilesystem`

```php
use Zenstruck\Filesystem\FlysystemFilesystem;

/** @var \League\Flysystem\FilesystemOperator $operator */
/** @var \League\Flysystem\FilesystemAdapter $adapter */

// create from an already configured Flysystem Filesystem Operator
$filesystem = new FlysystemFilesystem($operator);

// create from an already configured Flysystem Filesystem Adapter
$filesystem = new FlysystemFilesystem($operator);

// create for local directory
$filesystem = new FlysystemFilesystem('/path/to/local/dir');

// create for dsn (see available DSNs below)
$filesystem = new FlysystemFilesystem('ftp://user:pass@host.com:21/root');
```

#### Filesystem DSN

### `ScopedFilesystem`

### `MultiFilesystem`

### `LoggableFilesystem`

### `EventDispatcherFilesystem`

### `ArchiveFile`

## Testing

### `InteractsWithFilesystem`

#### `FilesystemProvider`

#### `FixtureFilesystemProvider`

### `ResetFilesystem`

## Glide Integration

### `GlideTransformUrlGenerator`

## Symfony Integration

### Responses

#### `FileResponse`

#### `ArchiveResponse`

### Validators

#### `PendingFileConstraint`

#### `PendingImageConstraint`

### Bundle

#### Configuration

#### Services

#### Serializer

#### Form Types

##### `PendingFileType`

##### `PendingImageType`

#### Commands

##### `zenstruck:filesystem:purge`

#### Routing

##### Public Url Route

##### Temporary Url Route

##### Transform Url Route

##### `RouteTransformUrlGenerator`

#### Doctrine Integration

#### Functional/Integration Testing

##### Testing Performance

### Full Default Bundle Configuration

```yaml
zenstruck_filesystem:

    # Filesystem configurations
    filesystems:

        # Prototype
        name:

            # Flysystem adapter DSN or, if prefixed with "@" flysystem adapter service id
            dsn:                  ~ # Required

                # Examples:
                # - '%kernel.project_dir%/public/files'
                # - 'ftp://foo:bar@example.com/path'
                # - 's3://accessKeyId:accessKeySecret@bucket/prefix#us-east-1'
                # - '@my_adapter_service'

            # Extra global adapter filesystem config
            config:               []

            # Public URL generator for this filesystem
            public_url:

                # URL prefix or multiple prefixes to use for this filesystem (can be an array)
                prefix:

                    # Examples:
                    # - /files
                    # - 'https://cdn1.example.com'
                    # - 'https://cdn2.example.com'

                # Service id for a League\Flysystem\UrlGeneration\PublicUrlGenerator
                service:              null

                # Generate with a route
                route:

                    # Route name
                    name:                 ~ # Required

                    # Route parameters
                    parameters:           []

                    # Sign by default?
                    sign:                 false

                    # Default expiry
                    expires:              null # Example: '+ 30 minutes'

            # Temporary URL generator for this filesystem
            temporary_url:

                # Service id for a League\Flysystem\UrlGeneration\TemporaryUrlGenerator
                service:              null

                # Generate with a route
                route:

                    # Route name
                    name:                 ~ # Required

                    # Route parameters
                    parameters:           []

            # Image Transform URL generator for this filesystem
            image_url:

                # Service id for a League\Flysystem\UrlGeneration\PublicUrlGenerator
                service:              null

                # Generate with a route
                route:

                    # Route name
                    name:                 ~ # Required

                    # Route parameters
                    parameters:           []

                    # Sign by default?
                    sign:                 false

                    # Default expiry
                    expires:              null # Example: '+ 30 minutes'

            # Dispatch filesystem operation events
            events:
                enabled:              false
                write:                true
                delete:               true
                mkdir:                true
                chmod:                true
                copy:                 true
                move:                 true

            # Log filesystem operations
            log:
                enabled:              true
                read:                 debug # One of false; "emergency"; "alert"; "critical"; "error"; "warning"; "notice"; "info"; "debug"
                write:                info # One of false; "emergency"; "alert"; "critical"; "error"; "warning"; "notice"; "info"; "debug"
                move:                 ~ # One of false; "emergency"; "alert"; "critical"; "error"; "warning"; "notice"; "info"; "debug"
                copy:                 ~ # One of false; "emergency"; "alert"; "critical"; "error"; "warning"; "notice"; "info"; "debug"
                delete:               ~ # One of false; "emergency"; "alert"; "critical"; "error"; "warning"; "notice"; "info"; "debug"
                chmod:                ~ # One of false; "emergency"; "alert"; "critical"; "error"; "warning"; "notice"; "info"; "debug"
                mkdir:                ~ # One of false; "emergency"; "alert"; "critical"; "error"; "warning"; "notice"; "info"; "debug"

            # If true, and using the ResetFilesystem trait
            # in your KernelTestCase's, delete this filesystem
            # before each test.
            reset_before_tests:   false

    # Default filesystem name used to autowire Zenstruck\Filesystem
    default_filesystem:   null

    # Doctrine configuration
    doctrine:
        enabled:              true

        # Global lifecycle events (can be disabled on a property-by-property basis)
        lifecycle:

            # Whether to auto load file type columns during object load
            autoload:             true

            # Whether to delete files on object removal
            delete_on_remove:     true
```

## Backward Compatibility Promise

This library follows [Symfony's BC Promise](https://symfony.com/doc/current/contributing/code/bc.html) with the
following exceptions:
1. `Zenstruck/Filesystem` and any implementations are considered _internal_ for _implementation_/_extension_.
2. `Zenstruck/Filesystem/Node` and any implementations are considered _internal_ for _implementation_/_extension_.
