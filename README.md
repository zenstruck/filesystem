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
2. Powerful [testing helpers](#testfilesystem).
3. [`ZipFile`](#zipfile)/[`TarFile`](#tarfile) representing a local zip/tar(.gz/bz2) file that acts as both a
   filesystem _and_ a real file.
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

// write operations (returns Zenstruck\Filesystem\File)
$filesystem->write('some/path.txt', 'string contents'); // write a string
$filesystem->write('some/path.txt', $resource); // write a resource
$filesystem->write('some/path.txt', new \SplFileInfo('path/to/local/file.txt')); // write a local file
$filesystem->write('some/path.txt', $file); // write a Zenstruck\Filesystem\Node\File

$filesystem->copy('from/file.txt', 'dest/file.txt'); // Zenstruck\Filesystem\Node\File (dest/file.txt)

$filesystem->move('from/file.txt', 'dest/file.txt'); // Zenstruck\Filesystem\Node\File (dest/file.txt)

$filesystem->delete('some/file.txt'); // returns self
$filesystem->delete('some/directory'); // returns self

// mkdir operations (returns Zenstruck\Filesystem\Node\Directory)
$filesystem->mkdir('some/directory'); // create an empty directory
$filesystem->mkdir('some/prefix', $directory); // create directory with files from Zenstruck\Filesystem\Node\Directory
$filesystem->mkdir('some/prefix', new \SplFileInfo('path/to/local/directory')); // create directory with files from local directory

$filesystem->chmod('some/file.txt', 'private'); // Zenstruck\Filesystem\Node (some/file.txt)

// utility methods
$filesystem->name(); // string - human-readable name for the filesystem
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

$file->stream(); // \Zenstruck\Stream - wrapper for a resource

$file->read(); // "raw" resource

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

> **Note**: See [`zenstruck/stream`](https://github.com/zenstruck/stream#zenstruckstream) for more
> details about `File::stream()`.

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

// get first matching node
$directories->first(); // null|\Zenstruck\Filesystem\Node
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
$filesystem = new FlysystemFilesystem('flysystem+ftp://user:pass@host.com:21/root');
```

#### Filesystem DSNs

| DSN                                                                  | Adapter                                                                                                                                                                                                                                        |
|----------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `%kernel.project_dir%/public/files`                                  | `LocalAdapter`                                                                                                                                                                                                                                 |
| `in-memory:`                                                         | `InMemoryFilesystemAdapter` (requires [`league/flysystem-memory`](https://flysystem.thephpleague.com/docs/adapter/in-memory/))                                                                                                                 |
| `in-memory:name`                                                     | _Static_ `InMemoryFilesystemAdapter` (requires [`league/flysystem-memory`](https://flysystem.thephpleague.com/docs/adapter/in-memory/))                                                                                                        |
| `flysystem+ftp://user:pass@host.com/root`                            | `FtpAdapter` (requires [`league/flysystem-ftp`](https://flysystem.thephpleague.com/docs/adapter/ftp/))                                                                                                                                         |
| `flysystem+ftps://user:pass@host.com/root`                           | `FtpAdapter` (requires [`league/flysystem-ftp`](https://flysystem.thephpleague.com/docs/adapter/ftp/))                                                                                                                                         |
| `flysystem+sftp://user:pass@host.com:22/root`                        | `SftpAdapter` (requires [`league/flysystem-sftp-v3`](https://flysystem.thephpleague.com/docs/adapter/sftp-v3/))                                                                                                                                |
| `flysystem+s3://accessKeyId:accessKeySecret@bucket/prefix#us-east-1` | `AsyncAwsS3Adapter`/`AwsS3V3Adapter` (requires [`league/flysystem-async-aws-s3`](https://flysystem.thephpleague.com/docs/adapter/async-aws-s3/) or [`league/flysystem-aws-s3-v3`](https://flysystem.thephpleague.com/docs/adapter/aws-s3-v3/)) |
| `readonly:<any-above-dsn>`                                           | `ReadOnlyFilesystemAdapter` (requires [`league/flysystem-read-only`](https://flysystem.thephpleague.com/docs/adapter/read-only/))                                                                                                              |

### `ScopedFilesystem`

```php
use Zenstruck\Filesystem\ScopedFilesystem;

/** @var \Zenstruck\Filesystem $primaryFilesystem */

$scopedFilesystem = new ScopedFilesystem($primaryFilesystem, 'some/prefix');

// paths are prefixed
$scopedFilesystem
    ->write('file.txt', 'content')
    ->path()->toString(); // "some/prefix/file.txt"
;

// prefix is stripped from path
$scopedFilesystem
    ->write('some/prefix/file.txt', 'content')
    ->path()->toString(); // "some/prefix/file.txt"
;
```

### `MultiFilesystem`

```php
use Zenstruck\Filesystem\MultiFilesystem;

/** @var \Zenstruck\Filesystem $filesystem1 */
/** @var \Zenstruck\Filesystem $filesystem2 */

$filesystem = new MultiFilesystem([
    'filesystem1' => $filesystem1,
    'filesystem2' => $filesystem2,
]);

// prefix paths with a "scheme" as the filesystem's name
$filesystem->file('filesystem1://some/file.txt'); // File from "filesystem1"
$filesystem->file('filesystem2://another/file.txt'); // File from "filesystem2"

// can copy and move across filesystems
$filesystem->copy('filesystem1://file.txt', 'filesystem2://file.txt');
$filesystem->move('filesystem1://file.txt', 'filesystem2://file.txt');

// set a default filesystem for when no scheme is set
$filesystem = new MultiFilesystem(
    [
        'filesystem1' => $filesystem1,
        'filesystem2' => $filesystem2,
    ],
    default: 'filesystem2'
);

$filesystem->file('another/file.txt'); // File from "filesystem2"
```

### `LoggableFilesystem`

> **Note**: A `psr/log-implementation` is required.

```php
use Zenstruck\Filesystem\LoggableFilesystem;
use Zenstruck\Filesystem\Operation;
use Psr\Log\LogLevel;

/** @var \Zenstruck\Filesystem $inner */
/** @var \Psr\Log\LoggerInterface $logger */

$filesystem = new LoggableFilesystem($inner, $logger);

// operations are logged
$filesystem->write('file.txt', 'content'); // logged as '[info] Writing "string" to "file.txt" on filesystem "<filesystem-name>"'

// customize the log levels for each operation
$filesystem = new LoggableFilesystem($inner, $logger, [
    Operation::READ => false, // disable logging read operations
    Operation::WRITE => LogLevel::DEBUG,
    Operation::MOVE => LogLevel::ALERT,
    Operation::COPY => LogLevel::CRITICAL,
    Operation::DELETE => LogLevel::EMERGENCY,
    Operation::CHMOD => LogLevel::ERROR,
    Operation::MKDIR => LogLevel::NOTICE,
]);
```

### `EventDispatcherFilesystem`

> **Note**: A `psr/event-dispatcher-implementation` is required.

```php
use Zenstruck\Filesystem\Event\EventDispatcherFilesystem;
use Zenstruck\Filesystem\Operation;

/** @var \Zenstruck\Filesystem $inner */
/** @var \Psr\EventDispatcher\EventDispatcherInterface $dispatcher */

$filesystem = new EventDispatcherFilesystem($inner, $dispatcher, [
    // set these to false or exclude to disable dispatching operation's event
    Operation::WRITE => true,
    Operation::COPY => true,
    Operation::MOVE => true,
    Operation::DELETE => true,
    Operation::CHMOD => true,
    Operation::MKDIR => true,
]);

$filesystem->write('foo', 'bar'); // PreWriteEvent/PostWriteEvent dispatched
$filesystem->mkdir('bar'); // PreMkdirEvent/PostMkdirEvent dispatched
$filesystem->chmod('foo', 'public'); // PreChmodEvent/PostChmodEvent dispatched
$filesystem->copy('foo', 'file.png'); // PreCopyEvent/PostCopyEvent dispatched
$filesystem->delete('foo'); // PreDeleteEvent/PostDeleteEvent dispatched
$filesystem->move('file.png', 'file2.png'); // PreMoveEvent/PostMoveEvent dispatched
;
```

> **Note**: See event classes to see what is made available to them.

> **Note**: The `Pre*Event` properties can be manipulated.

### `ZipFile`

> **Note**: `league/flysystem-ziparchive` is required (`composer require league/flysystem-ziparchive`).

This is a special filesystem wrapping a local zip archive. It acts as both a `Filesystem` and `\SplFileInfo` object:

```php
use Zenstruck\Filesystem\Archive\ZipFile;

$archive = new ZipFile('/local/path/to/archive.zip');
$archive->file('some/file.txt');
$archive->write('another/file.txt', 'content');

(string) $archive; // /local/path/to/archive.zip
```

When creating without a path, creates a temporary archive file (that's deleted at the end of the script):

```php
use Zenstruck\Filesystem\Archive\ZipFile;

$archive = new ZipFile();

$archive->write('some/file.txt', 'content');
$archive->write('another/file.txt', 'content');

(string) $archive; // /tmp/...
```

Write operations can be queued and committed via a _transaction_:

```php
use Zenstruck\Filesystem\Archive\ZipFile;

$archive = new ZipFile();

$archive->beginTransaction(); // start the transaction
$archive->write('some/file.txt', 'content');
$archive->write('another/file.txt', 'content');
$archive->commit(); // actually writes the above files

// optionally pass a progress callback to commit
$archive->commit(function() use ($progress) { // callback is called at most, 100 times
    $progress->advance();
});
```

Static helper for quickly creating `zip` archives:

```php
use Zenstruck\Filesystem\Archive\ZipFile;

$zipFile = ZipFile::compress('/some/local/file.txt');

// can take a local file, local directory, or instance of Zenstruck\Filesystem\Node\File|Directory
$zipFile = ZipFile::compress('some/local/directory'); // all files/directories (recursive) in "some/local/directory" are zipped
```

### `TarFile`

> **Note**: `league/flysystem-read-only` is required (`composer require league/flysystem-read-only`).

This is a special filesystem wrapping an existing local tar(.gz/bz2) archive. It acts as both a _readonly_
`Filesystem` and `\SplFileInfo` object:

```php
use Zenstruck\Filesystem\Archive\TarFile;

$archive = new TarFile('/local/path/to/archive.tar');
$archive = new TarFile('/local/path/to/archive.tar.gz');
$archive = new TarFile('/local/path/to/archive.tar.bz2');

$archive->file('some/file.txt'); // \Zenstruck\Filesystem\Node\File
```

## `TestFilesystem`

This filesystem wraps another and provides assertions for your tests. When using PHPUnit, these assertions are
converted to PHPUnit assertions.

> **Note**: `zenstruck/assert` is required to use the assertions (`composer require --dev zenstruck/assert`).

```php
use Zenstruck\Filesystem\Test\TestFilesystem;
use Zenstruck\Filesystem\Test\Node\TestDirectory;
use Zenstruck\Filesystem\Test\Node\TestFile
use Zenstruck\Filesystem\Test\Node\TestImage;

/** @var \Zenstruck\Filesystem $filesystem */

$filesystem = new TestFilesystem($filesystem);

$filesystem
    ->assertExists('foo')
    ->assertNotExists('invalid')
    ->assertFileExists('file1.txt')
    ->assertDirectoryExists('foo')
    ->assertImageExists('symfony.png')
    ->assertSame('symfony.png', 'fixture://symfony.png')
    ->assertNotSame('file1.txt', 'fixture://symfony.png')
    ->assertDirectoryExists('foo', function(TestDirectory $dir) {
        $dir
            ->assertCount(4)
            ->files()->assertCount(2)
        ;

        $dir
            ->recursive()
            ->assertCount(5)
            ->files()->assertCount(3)
        ;
    })
    ->assertFileExists('file1.txt', function(TestFile $file) {
        $file
            ->assertVisibilityIs('public')
            ->assertChecksum($file->checksum()->toString())
            ->assertContentIs('contents1')
            ->assertContentIsNot('foo')
            ->assertContentContains('1')
            ->assertContentDoesNotContain('foo')
            ->assertMimeTypeIs('text/plain')
            ->assertMimeTypeIsNot('foo')
            ->assertLastModified('2023-01-01 08:54')
            ->assertLastModified(function(\DateTimeInterface $actual) {
                // ...
            })
            ->assertSize(9)
        ;
    })
    ->assertImageExists('symfony.png', function(TestImage $image) {
        $image
            ->assertHeight(678)
            ->assertWidth(563)
        ;
    })
;

$file = $filesystem->realFile('symfony.png'); // \SplFileInfo('/tmp/symfony.png') - deleted at the end of the script
```

### `InteractsWithFilesystem`

Use the `InteractsWithFilesystem` trait in your unit tests to quickly provide an in-memory filesystem.

> **Note**: By default, `league/flysystem-memory` is required (`composer require --dev league/flysystem-memory`).

```php
use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;

class MyTest extends TestCase
{
    use InteractsWithFilesystem;

    public function test_1(): void
    {
        $filesystem = $this->filesystem(); // instance of TestFilesystem wrapping an in-memory filesystem
        $filesystem->write('file.txt', 'content');
        $filesystem->assertExists('file.txt');
    }
}
```

#### `FilesystemProvider`

To provide your own filesystem for your tests, have your tests (or base test-case) implement `FilesystemProvider`:

```php
use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;
use Zenstruck\Filesystem\Test\FilesystemProvider;

class MyTest extends TestCase implements FilesystemProvider
{
    use InteractsWithFilesystem;

    public function test_1(): void
    {
        $filesystem = $this->filesystem(); // instance of TestFilesystem wrapping the AdapterFilesystem defined below
        $filesystem->write('file.txt', 'content');
        $filesystem->assertExists('file.txt');
    }

    public function createFilesystem(): Filesystem|FilesystemAdapter|string;
    {
        return '/some/temp/dir';
    }
}
```

> **Note**: By default, the provided filesystem isn't reset before each test. See the
> [`ResetFilesystem`](#resetfilesystem) to enable this behaviour.

#### `FixtureFilesystemProvider`

A common requirement for filesystem tests, is to have a set of known fixture files that are used in your tests.
Have your test's (or base test-case) implement `FixtureFilesystemProvider` to provide in your tests:

```php
use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;
use Zenstruck\Filesystem\Test\FixtureFilesystemProvider;

class MyTest extends TestCase implements FixtureFilesystemProvider
{
    use InteractsWithFilesystem;

    public function test_1(): void
    {
        $filesystem = $this->filesystem(); // instance of TestFilesystem wrapping a MultiFilesystem

        $filesystem->write('file.txt', 'content'); // accesses your test filesystem
        $filesystem->assertExists('file.txt');
        $filesystem->copy('fixture://some/file.txt', 'file.txt'); // copy a fixture to your test filesystem
    }

    public function createFixtureFilesystem(): Filesystem|FilesystemAdapter|string;
    {
        return __DIR__.'/../fixtures';
    }
}
```

> **Note**: If the [`league/flysystem-read-only`](https://flysystem.thephpleague.com/docs/adapter/read-only/)
> adapter is available, it's used to wrap your fixture adapter to ensure you don't accidentally overwrite/delete
> your fixture files (`composer require --dev league/flysystem-read-only`).

### `ResetFilesystem`

If using your own [`FilesystemProvider`](#filesystemprovider), you can use the `ResetFilesystem` trait to
purge your filesystem before each test.

```php
use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Test\ResetFilesystem
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;
use Zenstruck\Filesystem\Test\FilesystemProvider;

class MyTest extends TestCase implements FilesystemProvider
{
    use InteractsWithFilesystem, ResetFilesystem;

    public function test_1(): void
    {
        $this->filesystem()->write('file.txt', 'content')
        $this->filesystem()->assertExists('file.txt')
    }

    public function test_2(): void
    {
        $this->filesystem()->assertNotExists('file.txt'); // file created in test_1 was deleted before this test
    }

    public function createFilesystem(): Filesystem|FilesystemAdapter|string;
    {
        return '/some/temp/dir';
    }
}
```

## Symfony Integration

### Responses

Helpful custom Symfony responses are provided.

#### `FileResponse`

Take a filesystem [`File`](#file) and send as a response:

```php
use Zenstruck\Filesystem\Symfony\HttpFoundation\FileResponse;

/** @var \Zenstruck\Filesystem\File $file */

$response = new FileResponse($file); // auto-adds content-type/last-modified headers

// create inline/attachment responses
$response = FileResponse::attachment($file); // auto names by the filename (file.txt)
$response = FileResponse::inline($file); // auto names by the filename (file.txt)

// customize the filename used for the content-disposition header
$response = FileResponse::attachment($file, 'different-name.txt');
$response = FileResponse::inline($file, 'different-name.txt');
```

#### `ArchiveResponse`

Zip file(s) and send as a response. Can be created with a local file, local directory, instance of
[`File`](#file) or instance of [`Directory`](#directory).

```php
use Zenstruck\Filesystem\Symfony\HttpFoundation\ArchiveResponse;

/** @var \SplFileInfo|\Zenstruck\Filesystem\Node\File|\Zenstruck\Filesystem\Node\Directory $what */

$response = ArchiveResponse::zip($what);
$response = ArchiveResponse::zip($what, 'data.zip'); // customize the content-disposition name (defaults to archive.zip)
```

### Validators

Both a [`PendingFile`](#pendingfile) and [`PendingImage`](#pendingimage) validator is provided. The constraints have
the same API as Symfony's native [`File`](https://symfony.com/doc/current/reference/constraints/File.html) and
[`Image`](https://symfony.com/doc/current/reference/constraints/Image.html) constraints.

```php
use Zenstruck\Filesystem\Symfony\Validator\PendingFileConstraint;
use Zenstruck\Filesystem\Symfony\Validator\PendingImageConstraint;

/** @var \Symfony\Component\Validator\Validator\ValidatorInterface $validator */
/** @var \Zenstruck\Filesystem\Node\File $file */
/** @var \Zenstruck\Filesystem\Node\File\Image $image */

$validator->validate($file, new PendingFileConstraint(maxSize: '1M')));

$validator->validate($image, new PendingImageConstraint(maxWidth: 200, maxHeight: 200)));
```

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
                # - 'flysystem+ftp://foo:bar@example.com/path'
                # - 'flysystem+s3://accessKeyId:accessKeySecret@bucket/prefix#us-east-1'
                # - 'static-in-memory'
                # - 'scoped:<name>:<prefix>'
                # - '@my_adapter_service'

            # Extra global adapter filesystem config
            config:               []

            # Lazily load the filesystem when the first call occurs (requires Symfony 6.2+)
            lazy:                 true/false # if symfony/var-exporter 6.2+ is installed, true, false otherwise

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
