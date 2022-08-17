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

1. System to easily generate publicly accessible urls for your files.
2. Filesystem _wrappers_ to add additional functionality (ie [`MultiFilesystem`](#multifilesystem),
   [`ReadonlyFilesystem`](#readonlyfilesystem) and [`LoggableFilesystem`](#loggablefilesystem)).
3. Powerful [testing helpers](#testfilesystem).
4. [`ArchiveFile`](#archivefile) representing a local zip file that acts as both a filesystem _and_ a real file.
5. [Doctrine Integration](#doctrine-integration).
6. [Symfony Integration](#symfony-integration)
   - [Custom Responses](#responses)
   - [Validators](#validators)
   - [Bundle](#symfony-bundle) to help configure filesystem services, wire the Doctrine integration and additional
     testing helpers.

## Installation

```bash
composer require zenstruck/filesystem
```

## API

All filesystems implement the `Zenstruck/Filesystem` interface which has the following
API:

### Write Operations

```php
/** @var Zenstruck\Filesystem $filesystem */

// copy
$filesystem->copy('some/file.txt', 'another/file.txt'); // copy file
$filesystem->copy('some/dir', 'another/dir'); // copy directory

// move
$filesystem->move('some/file.txt', 'another/file.txt'); // move file
$filesystem->move('some/dir', 'another/dir'); // move directory

// delete
$filesystem->delete('some/file.txt'); // delete a file
$filesystem->delete('some/dir'); // delete a directory

// create directory
$filesystem->mkdir('some/path');

// change visibility
$filesystem->chmod('some/path', 'private'); // @see Flysystem's setVisibility()
```

#### Write

`Zenstruck/Filesystem::write()` is powerful and allows writing many different things:

```php
/** @var Zenstruck\Filesystem $filesystem */

// write string content
$filesystem->write('some/file.txt', 'some file content');

// write stream
$filesystem->write('some/file.txt', $resource);

// write local file
$filesystem->write('some/file.txt', new \SplFileInfo('/path/to/local/file.txt'));
```

##### Modifying an Existing File

Many 3rd party libraries require a _real, local file_ to perform operations. You can provide
a callback to the second `write()` argument to manipulate a real file:

```php
/** @var Zenstruck\Filesystem $filesystem */

$filesystem->write('existing/file.csv', function(\SplFileInfo $file) {
    $some3rdPartyService->manipulate($file);

    return $file; // the file returned from the callback is written to "exising/file.csv"
});
```

> **Note**:
> If you need a real, local file to pass around you can use `$filesystem->file('existing/file.csv')->tempFile()`.
> This file is temporary and will be deleted at the end of the script. It is up to you to save it back to your
> filesystem.

### Read Operations

```php
/** @var Zenstruck\Filesystem $filesystem */

// check for existence
$filesystem->has('some/file.txt'); // true/false
$filesystem->has('some/dir'); // true/false

/**
 * get directory node
 *
 * @throws \Zenstruck\Filesystem\Exception\NodeNotFound If does not exist
 * @throws \Zenstruck\Filesystem\Exception\NodeTypeMismatch If exists but not a directory
 */
$directory = $filesystem->directory('some/dir'); // Zenstruck\Node\Directory (see below)

/**
 * get file node
 *
 * @throws \Zenstruck\Filesystem\Exception\NodeNotFound If does not exist
 * @throws \Zenstruck\Filesystem\Exception\NodeTypeMismatch If exists but not a file
 */
$file = $filesystem->file('some/file.txt'); // Zenstruck\Node\File (see below)

/**
 * get image node
 *
 * @throws \Zenstruck\Filesystem\Exception\NodeNotFound If does not exist
 * @throws \Zenstruck\Filesystem\Exception\NodeTypeMismatch If exists but not an image file
 */
$image = $filesystem->image('some/file.txt'); // Zenstruck\Node\File\Image (see below)

/**
 * get any node
 *
 * @throws \Zenstruck\Filesystem\Exception\NodeNotFound If does not exist
 */
$node = $filesystem->node('some/node'); // Zenstruck\Node\File|Zenstruck\Node\Directory (see below)

/**
 * get last operated on node
 *
 * @throws \LogicException If there was no last operation or the last operation was a delete
 */
$node = $filesystem->write('file.txt', 'content')->last(); // Zenstruck\Node\File (for "file.txt")
```

### Nodes

#### `File`

```php
/** @var Zenstruck\Filesystem $filesystem */

$file = $filesystem->file('nested/file.txt');

$file->path(); // "nested/file.txt"
(string) $file; // "nested/file.txt"
$file->name(); // "file.txt"
$file->dirname(); // "nested"
$file->extension(); // "txt"
$file->nameWithoutExtension(); // "file"

$file->directory(); // Zenstruck\Node\Directory for the file's parent directory (ie "nested")

$file->contents(); // string of the file's contents
$file->read(); // resource stream of the file's contents

// get a "temporary, local, real file"
$file->tempFile(); // \SplFileInfo (deleted at the end of the script)

/**
 * get the url
 *
 * @throws \Zenstruck\Filesystem\Exception\UnsupportedFeature If your filesystem does not support urls
 */
$url = $file->url(); // Zenstruck\Uri
(string) $url; // string (ie https://example.com/nested/file.txt)

// metadata
$file->lastModified(); // \DateTimeImmutable
$file->mimeType(); // string (ie text/plain)
$file->visibility(); // string (ie public/private)

// size
$size = $file->size(); // Zenstruck\Dimension\Information (see https://github.com/zenstruck/dimension#information-object)
(string) $size; // string (ie 23234 B)
$size->bytes(); // int (file size in bytes)
$size->humanize(); // string (ie 1.1 kB)
$size->asDecimal()->humanize(); // string (ie 1.1 KiB)
$size->isLargerThan('1.0 kB'); // bool
$size->isSmallerThan('1.0 kB'); // bool

// checksum
$checksum = $file->checksum(); // Zenstruck\Filesystem\Node\File\Checksum
(string) $checksum; // md5 hash of file contents
(string) $checksum->useSha1(); // sha1 hash of file contents
(string) $checksum->forMetadata(); // md5 hash of file's metadata (size, mime-type, last-modified)
(string) $checksum->forMetadata()->useSha1(); // sha1 hash of file's metadata (size, mime-type, last-modified)

$checksum->equals($anotherChecksum); // bool
```

#### `Image`

The `Image` node extends `File` so all it's methods are available.

```php
/** @var Zenstruck\Filesystem $filesystem */

$image = $filesystem->image('nested/image.png');

/**
 * get the url to Glide server
 *
 * @throws \Zenstruck\Filesystem\Exception\UnsupportedFeature If your filesystem does not support Glide URLs
 */
$url = $file->glideUrl(['w' => 100, 'h' => 100]); // Zenstruck\Uri
(string) $url; // string (ie https://example.com/glide/image.png?w=100&h=100)

// image metadata
$image->width(); // int
$image->height(); // int
$image->pixels(); // int - width x height
$image->aspectRatio(); // float
$image->isLandscape(); // bool
$image->isPortrait(); // bool
$image->isSquare(); // bool
```

#### `Directory`

```php
/** @var Zenstruck\Filesystem $filesystem */

$dir = $filesystem->directory('nested/dir');

$dir->path(); // "nested/dir"
(string) $dir; // "nested/dir"
$dir->name(); // "dir"
$dir->dirname(); // "nested"

// create a zip archive
$archive = $dir->zip(); // Zenstruck\Filesystem\ArchiveFile (see below)

// metadata
$file->lastModified(); // \DateTimeImmutable
$file->visibility(); // string (ie public/private)

// iterable (non-recursive by default)
foreach ($dir as $node) {
    // $node = Zenstruck\Filesystem\Node\File|Zenstruck\Filesystem\Node\Directory
}

// iterable - recursive
foreach ($dir->recursive() as $node) {
    // $node = Zenstruck\Filesystem\Node\File|Zenstruck\Filesystem\Node\Directory
}

// iterable - recursive, files only
foreach ($dir->recursive()->files() as $file) {
    // $file = Zenstruck\Filesystem\Node\File
}

// iterable - recursive, directories only
foreach ($dir->recursive()->directories() as $directory) {
    // $directory = Zenstruck\Filesystem\Node\Directory
}

// advanced filters
$filtered = $dir
    ->recursive()
    ->files()
    ->olderThan('yesterday')
    ->newerThan('-1 year')
    ->largerThan('1mb')
    ->smallerThan('1gb')
    ->matchingName('*.txt')
    ->notMatchingName(['foo*', 'bar*'])
    ->filter(function(\Zenstruck\Filesystem\Node\File|\Zenstruck\Filesystem\Node\File $node) {
        // add your own filter, return true if should be included
    })
;

foreach ($filtered as $file) {
    // $file = Zenstruck\Filesystem\Node\File
}
```

## Filesystems

### `AdapterFilesystem`

This is the primary filesystem, it can be created with any Flysystem Adapter (object that
implements `League\Flysystem\FilesystemAdapter`). Alternatively, it can be created with a
string for a local file path to create a `LocalFilesystemAdapter`.

```php
use Zenstruck\Filesystem\AdapterFilesystem;

/** @var League\Flysystem\FilesystemAdapter $adapter */

$filesystem = new AdapterFilesystem($adapter);

// give your filesystem a name (used for logging, etc)
$filesystem = new AdapterFilesystem($adapter, ['name' => 'public']);

// create from local path
$filesystem = new AdapterFilesystem('/some/local/path');
```

#### Flysystem Adapters

While any League adapter can be used, this library comes with some additional ones:

##### `StaticInMemoryAdapter`

> **Note**: Requires `league/flysystem-memory`.

This works like `InMemoryFilesystemAdapter` but stores the filesystem data in a static
variable. This is useful when writing Symfony tests that reset services between requests.

```php
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\Adapter\StaticInMemoryAdapter;

$filesystem = new AdapterFilesystem(new StaticInMemoryAdapter());
$filesystem->write('file.txt', 'content');

// create a new one
$filesystem = new AdapterFilesystem(new StaticInMemoryAdapter());
$filesystem->file('file.txt'); // works!

// create with different names for multiple static filesystems
$filesystem1 = new AdapterFilesystem(new StaticInMemoryAdapter('first'));
$filesystem2 = new AdapterFilesystem(new StaticInMemoryAdapter('second'));

// reset all data
StaticInMemoryAdapter::reset();
```

#### Features

Features are a set of interfaces that can be used to add/improve the functionality of your filesystem. These can be
implemented by League adapters themselves or a separate object that's added to `AdapterFilesystem`. Here are the
currently available features:

- `Zenstruck\Filesystem\Feature\FileUrl`: enables the usage of `File::url()` (implemented by
  [`PrefixFileUrlFeature`](#prefixfileurlfeature)).
- `Zenstruck\Filesystem\Feature\GlideUrl`: enables the usage of `Image::glideUrl()` (implemented by
    [`UrlBuilderGlideUrlFeature`](#urlbuilderglideurlfeature)).
- `Zenstruck\Filesystem\Feature\ModifyFile`: provides a real file for modification/metadata. If not implemented,
  creates/uses a temporary file on the filesystem. This is implemented by the default `LocalAdapter` provided by
  this library.
- `Zenstruck\Filesystem\Feature\FileChecksum`: provides efficient file content checksum calculations. If not
  implemented, calculates based on the file content string, which, depending on the adapter/file size, could
  be slow. This is implemented by the default `LocalAdapter` provided by this library.

##### `PrefixFileUrlFeature`

Use this to prefix you filesystem file path's with one or more prefixes:

```php
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\Feature\FileUrl\PrefixFileUrlFeature;

$filesystem = new AdapterFilesystem('/path/to/root', features: [
    new PrefixFileUrlFeature('/files'),
]);

(string) $filesystem->file('some/file.txt')->url(); // "/files/some/file.txt"

// use an array of prefixes to alternate (deterministically) between prefixes

$filesystem = new AdapterFilesystem('/path/to/root', features: [
    new PrefixFileUrlFeature(['https://cdn1.example.com/files', 'https://cdn2.example.com/files']),
]);

(string) $filesystem->file('some/file.txt')->url(); // "https://cdn1.example.com/files/some/file.txt"
(string) $filesystem->file('another/file.txt')->url(); // "https://cdn2.example.com/files/another/file.txt"
```

##### `UrlBuilderGlideUrlFeature`

> **Note**: Requires `league/glide`.

Use this to build paths to Glide server for image manipulation (thumbnails etc.):

```php
use League\Glide\Urls\UrlBuilderFactory;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\Feature\FileUrl\PrefixFileUrlFeature;

$filesystem = new AdapterFilesystem('/path/to/root', features: [
    new UrlBuilderGlideUrlFeature(UrlBuilderFactory::create('/glide/', 'signature')),
]);

(string) $filesystem->image('some/image.png')->glideUrl(['w' => 100, 'h' => 100]); // "/glide/some/image.png?w=100&h=100&s=3a82607a9517525d0a98de4548e10917"
```

##### Custom Features

You can add your own custom features by:

1. Creating a `FilesystemAdapter` that implements the feature's interface and creating your `AdapterFilesystem` with
   this adapter.
2. Creating a class that implements the feature's interface and adding to the `AdapterFilesystem`'s feature set.

Let's look at a _meta-code_ example that creates a (optionally temporary) signed file url from S3:

```php
use Zenstruck\Filesystem\Feature\FileUrl;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Uri;

class S3FileUrlFeature implements FileUrl
{
    public function __construct(private $someS3Client) {}

    public function urlFor(File $file, mixed $options = []): Uri
    {
        $url = $this->someS3Client->createSignedUrl($file->path(), ['expires' => $options['expiry'] ?? null]);

        return Uri::new($url);
    }
}
```

Create your adapter with this feature and use:

```php
use Zenstruck\Filesystem\AdapterFilesystem;

$filesystem = new AdapterFilesystem($yourAdapter, features: [new S3FileUrlFeature($s3Client)]);

$filesystem->file('some/file.txt')->url(); // creates a signed url from your feature

$filesystem->file('some/file.txt')->url(['expires' => 'tomorrow']); // creates a temporary signed url from your feature
```

### `MultiFilesystem`

This filesystem wraps multiple filesystems and allows operations across them by prefixing the path
with a _scheme_ (ie `first://some/file.txt`):

```php
use Zenstruck\Filesystem\MultiFilesystem;

/** @var \Zenstruck\Filesystem $first */
/** @var \Zenstruck\Filesystem $second */

$filesystem = new MultiFilesystem([
    'first' => $first,
    'second' => $second,
]);

$filesystem->file('first://some/file.txt'); // get the file from the first filesystem
$filesystem->file('second://some/file.txt'); // get the file from the second filesystem

$filesystem->copy('first://some/file.txt', 'first://another/file.txt');

// you can even copy/move across filesystems
$filesystem->copy('first://some/file.txt', 'second://another/file.txt');

// access the underlying filesystems by name:
$first = $filesystem->get('first');
```

A default can be provided - this removes the need to prefix path's with a scheme for this filesystem:

```php
use Zenstruck\Filesystem\MultiFilesystem;

/** @var \Zenstruck\Filesystem $first */
/** @var \Zenstruck\Filesystem $second */

$filesystem = new MultiFilesystem([
    'first' => $first,
    'second' => $second,
], default: 'first');

$filesystem->file('some/file.txt'); // get the file from the first filesystem
$filesystem->file('second://some/file.txt'); // get the file from the second filesystem
```

### `ReadonlyFilesystem`

Filesystem wrapper that prevents write operations (throws `\BadMethodCallException`'s if attempted):

```php
use Zenstruck\Filesystem\ReadonlyFilesystem;

/** @var \Zenstruck\Filesystem $filesystem */

$filesystem = new ReadonlyFilesystem($filesystem);

$filesystem->file('some/file.txt'); // ok
$filesystem->delete('some/file.txt'); // throws \BadMethodCallException
```

### `LoggableFilesystem`

Filesystem wrapper that enables logging operations.

> **Note**: If using multiple filesystems, it's important they are named to distinguish in logs.

```php
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\LoggableFilesystem;

/** @var \Psr\Log\LoggerInterface $logger */

$filesystem = new AdapterFilesystem('/some/root', ['name' => 'public']); // note the name "public"
$filesystem = new LoggableFilesystem($filesystem, $logger);

$filesystem->file('some/file.txt'); // logs "[debug] Read "some/file.txt" (file) on filesystem "public""
$filesystem->write('file1.txt', 'content'); // logs "[info] Wrote "string" to "file1.txt" on filesystem "public""
$filesystem->delete('file1.txt'); // logs "[info] Deleted "file1.txt" on filesystem "public""
```

By default, read operations are logged at the _debug_ level and write operations are logged at the _info_ level.
This can be customized:

```php
use Zenstruck\Filesystem\LoggableFilesystem;
use Psr\Log\LogLevel

/** @var \Zenstruck\Filesystem $filesystem */
/** @var \Psr\Log\LoggerInterface $logger */

$filesystem = new LoggableFilesystem($filesystem, $logger, [
    'read' => false, // disable logging read operations
    'delete' => LogLevel::NOTICE, // use the "notice" level for delete operations
]);
```

### `ArchiveFile`

This is a special filesystem wrapping a zip archive. It acts as both a `Filesystem` and `\SplFileInfo` object:

```php
use Zenstruck\Filesystem\ArchiveFile;

$archive = new ArchiveFile('/local/path/to/archive.zip');
$archive->file('some/file.txt');
$archive->write('another/file.txt', 'content');

(string) $archive; // /local/path/to/archive.zip
```

When creating without a path, creates a temporary archive file (that's deleted at the end of the script):

```php
use Zenstruck\Filesystem\ArchiveFile;

$archive = new ArchiveFile();

$archive->write('some/file.txt', 'content');
$archive->write('another/file.txt', 'content');

(string) $archive; // /tmp/...
```

Write operations can be queued and committed via a _transaction_:

```php
use Zenstruck\Filesystem\ArchiveFile;

$archive = new ArchiveFile();

$archive->beginTransaction(); // start the transaction
$archive->write('some/file.txt', 'content');
$archive->write('another/file.txt', 'content');
$archive->commit(); // actually writes the above files

// optionally pass a progress callback to commit
$archive->commit(function() use ($progress) { // callback is called at most, 100 times
    $progress->advance();
});
```

Static helpers for quickly creating `zip`, `tar`, `tar.gz` and `tar.bz2` archives:

```php
use Zenstruck\Filesystem\ArchiveFile;

$zipFile = ArchiveFile::zip('/some/local/file.txt');
$tarFile = ArchiveFile::tar('/some/local/file.txt');
$tarGzFile = ArchiveFile::tarGz('/some/local/file.txt');
$tarBz2File = ArchiveFile::tarBz2('/some/local/file.txt');

// the above methods can take a local file, local directory, or instance of Zenstruck\Filesystem\Node\File|Directory
$zipFile = ArchiveFile::zip('some/local/directory'); // all files/directories (recursive) in "some/local/directory" are zipped
```

### `TestFilesystem`

> **Note**: `zenstruck/assert` is required to use the assertions (`composer require --dev zenstruck/assert`).

This filesystem wraps another and provides assertions for your tests. When using PHPUnit, these assertions
are converted to PHPUnit assertions.

```php
use Zenstruck\Filesystem\Test\TestFilesystem;

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
            ->assertChecksum(function(Checksum $actual) use ($file) {
                $this->assertSame($file->checksum()->useSha1()->toString(), $actual->useSha1()->toString());
            })
            ->assertContentIs('contents1')
            ->assertContentIsNot('foo')
            ->assertContentContains('1')
            ->assertContentDoesNotContain('foo')
            ->assertMimeTypeIs('text/plain')
            ->assertMimeTypeIsNot('foo')
            ->assertLastModified(function(\DateTimeInterface $actual) {
                $this->assertTrue($actual->getTimestamp() > 0);
            })
            ->assertSize(9)
            ->assertSize(function(Information $actual) {
                $this->assertTrue($actual->isSmallerThan('1mb'));
            })
        ;
    })
    ->assertImageExists('symfony.png', function(TestImage $image) {
        $image
            ->assertHeight(678)
            ->assertWidth(563)
            ->assertHeight(function($actual) {
                $this->assertGreaterThan(600, $actual);
                $this->assertLessThan(700, $actual);
            })
            ->assertWidth(function($actual) {
                $this->assertGreaterThan(500, $actual);
                $this->assertLessThan(600, $actual);
            })
        ;
    })
;
```

#### `InteractsWithFilesystem`

Use the `InteractsWithFilesystem` trait in your unit tests to quickly provide an in-memory filesystem
that is reset before each test:

> **Note**: By default, `league/flysystem-memory` is required: `composer require --dev league/flysystem-memory`.

```php
use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;

class MyTest extends TestCase
{
    use InteractsWithFilesystem;

    public function test_1(): void
    {
        $this->filesystem() // instance of TestFilesystem wrapping an in-memory filesystem
            ->write('file.txt', 'content')
            ->assertExists('file.txt')
        ;
    }
}
```

##### `TestFilesystemProvider`

To use a different filesystem for your tests, have your test's (or base class) implement `TestFilesystemProvider`:

```php
use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;
use Zenstruck\Filesystem\Test\TestFilesystemProvider;

class MyTest extends TestCase implements TestFilesystemProvider
{
    use InteractsWithFilesystem;

    public function test_1(): void
    {
        $this->filesystem() // instance of TestFilesystem wrapping the AdapterFilesystem defined below
            ->write('file.txt', 'content')
            ->assertExists('file.txt')
        ;
    }

    public function getTestFilesystem(): string|Filesystem
    {
        return '/some/temp/dir'; // creates an AdapterFilesystem using the LocalAdapter for this directory
    }
}
```

##### `FixtureFilesystemProvider`

A common requirement for filesystem tests, is to have a set of known fixture files that are used in your tests.
Have your test's (or base class) implement `FixtureFilesystemProvider` to provide in your tests:

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

        $filesystem
            ->write('file.txt', 'content') // accesses your test filesystem
            ->assertExists('file.txt')
            ->copy('fixture://some/file.txt', 'file.txt') // copy a fixture to your test filesystem
        ;
    }

    public function getFixtureFilesystem(): string|Filesystem
    {
        return __DIR__.'/../fixtures';
    }
}
```

> **Note**: The fixture filesystem is readonly so you don't accidentally override your fixture files.

## Doctrine Integration

> **Note**: Currently, Doctrine integration is only documented in the context of the [Symfony Bundle](#doctrine-entities).

## Symfony Integration

### Responses

Helpful custom Symfony responses are provided:

#### `FileResponse`

Take a filesystem [`File`](#file) and send as a response:

```php
use Zenstruck\Filesystem\Bridge\Symfony\HttpFoundation\FileResponse;

/** @var \Zenstruck\Filesystem $filesystem */

$file = $filesystem->file('some/file.txt');

$response = new FileResponse($file); // auto-adds content-type/last-modified headers

// create inline/attachment responses
$response = FileResponse::attachment($file); // auto names by the filename (file.txt)
$response = FileResponse::inline($file); // auto names by the filename (file.txt)

// customize the filename used for the content-disposition header
$response = FileResponse::attachment($file, 'different-name.txt');
$response = FileResponse::inline($file, 'different-name.txt');
```

#### `ArchiveFileResponse`

Quickly zip file(s) and send as a response. Can be created with a local file, local directory, instance of
[`File`](#file) or instance of [`Directory`](#directory).

```php
use Zenstruck\Filesystem\Bridge\Symfony\HttpFoundation\ArchiveFileResponse;

/** @var \SplFileInfo|\Zenstruck\Filesystem\Node\File|\Zenstruck\Filesystem\Node\Directory $what */

$response = ArchiveFileResponse::zip($what);
$response = ArchiveFileResponse::zip($what, 'data.zip'); // customize the content-disposition name (defaults to archive.zip)

$response = ArchiveFileResponse::tarGz($what);
$response = ArchiveFileResponse::tarGz($what, 'data.tar.gz'); // customize the content-disposition name (defaults to archive.tar.gz)

$response = ArchiveFileResponse::tarBz2($what);
$response = ArchiveFileResponse::tarBz2($what, 'data.tar.bz2'); // customize the content-disposition name (defaults to archive.tar.bz2)
```

### Validators

Both a file and image validator is provided. The constraints have the same API as Symfony's native
[File](https://symfony.com/doc/current/reference/constraints/File.html) and
[Image](https://symfony.com/doc/current/reference/constraints/Image.html) constraints.

```php
use Zenstruck\Filesystem\Bridge\Symfony\Validator\Constraints\File;
use Zenstruck\Filesystem\Bridge\Symfony\Validator\Constraints\Image;

/** @var \Symfony\Component\Validator\Validator\ValidatorInterface $validator */
/** @var \Zenstruck\Filesystem $filesystem */

$validator->validate($filesystem->file('file.txt', new File(maxSize: '1M')));

$validator->validate($filesystem->image('image.png', new Image(maxWidth: 200, maxHeight: 200)));
```

### Symfony Bundle

A bundle is available to configure different filesystems for your application. Enable in your `config/bundles.php`:

```php
// config/bundles.php

return [
    // ...
    Zenstruck\Filesystem\Bridge\Symfony\ZenstruckFilesystemBundle::class => ['all' => true]
];
```

#### Configuration

Configure your application's filesystems:

```yaml
# config/packages/zenstruck_filesystem.yaml

zenstruck_filesystem:
    filesystems:
        public: # create a public filesystem that has a public url
            dsn: '%kernel.project_dir%/public/files'
            url_prefix: /files # prefix File::url() path's with "/files"

        private: # create a private filesystem that is readonly
            dsn: '%kernel.project_dir%/var/files'
            readonly: true

    default_filesystem: public # set the "default" filesystem (defaults to first one defined above)
```

By default, defined filesystems have logging enabled. In your development environment, a profiler toolbar item
is available summarizing the operations made across your filesystem(s):

![toolbar screenshot](docs/assets/toolbar.png)

##### Available DSN's

The following can be used for a filesystem adapter _DSN's_:

| DSN                                                        | Adapter                                                 |
|------------------------------------------------------------|---------------------------------------------------------|
| `%kernel.project_dir%/public/files`                        | `LocalAdapter`                                          |
| `in-memory:`                                               | `InMemoryFilesystemAdapter`                             |
| `in-memory:?static#name`                                   | `StaticInMemoryAdapter`                                 |
| `ftp://user:pass@host.com:21/root`                         | `FtpAdapter`                                            |
| `ftps://user:pass@host.com:21/root`                        | `FtpAdapter`                                            |
| `sftp://user:pass@host.com:22/root`                        | `SftpAdapter`                                           |
| `s3://accessKeyId:accessKeySecret@bucket/prefix#us-east-1` | `AsyncAwsS3Adapter`/`AwsS3V3Adapter`                    |
| `@service_id`                                              | _Custom service id that implements `FilesystemAdapter`_ |

#### Services

Your defined filesystems can be autowired:

```php
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\MultiFilesystem;

class SomeController
{
    public function someAction(
        Filesystem $publicFilesystem, // the public filesystem as defined in your config
        Filesystem $privateFilesystem, // the private filesystem as defined in your config
        Filesystem $filesystem, // an instance of MultiFilesystem wrapping both public/private filesystems
        MultiFilesystem $multiFilesystem, // same as above
    ) {
        $publicFilesystem->file('some/file.txt')->url()->toString(); // "/files/some/file.txt"

        $filesystem->copy('private://file.txt', 'public://file.txt'); // copy a private file to the public filesystem
        $filesystem->copy('private://file.txt', 'file.txt'); // (same as above) default filesystem scheme can be omitted

        $multiFilesystem->get('public'); // access the public filesystem
        $multiFilesystem->get('private'); // access the private filesystem

        // ...
    }
}
```

#### Testing

By default, in your `test` environment, your defined filesystem adapters are swapped with local adapters whose
files live in `var/testfiles/{filesystem-name}`. When creating integration/functional tests (tests that extend
`KernelTestCase`), use the [`InteractsWithFilesystem`](#interactswithfilesystem) trait to access your test
filesystems.

This trait automatically detects of you are in an integration/functional tests and makes your filesystems available.
Additionally, it deletes the files before each test. This allows you to run a single test and debug the created
files after.

```php
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;

class MyTest extends KernelTestCase
{
    use InteractsWithFilesystem;

    public function test_something(): void
    {
        $filesystem = $this->filesystem(); // instance of TestFilesystem wrapping a MultiFilesystem containing defined test filesystems

        $filesystem->file('public://some/file.txt'); // access file from public filesystem
        $filesystem->file('some/file.txt'); // (same as above) default filesystem scheme can be omitted

        $filesystem->file('private://another/file.txt'); // access file from private filesystem
    }
}
```

> **Note**: See the primary [testing documentation](#testfilesystem) to see all available assertions.

You can have your integration tests implement the [`FixtureFilesystemProvider`](#fixturefilesystemprovider)
interface to provide fixtures:

```php
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Filesystem\Test\FixtureFilesystemProvider;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;

class MyTest extends KernelTestCase implements FixtureFilesystemProvider
{
    use InteractsWithFilesystem;

    public function test_something(): void
    {
        $this->filesystem()->file('fixture://file.txt'); // access fixture files
    }

    public function getFixtureFilesystem(): string|Filesystem
    {
        return return __DIR__.'/../fixtures';
    }
}
```

##### Test Performance

You can increase the speed of your tests by switching your test filesystems to use an in-memory adapter:

> **Note**: `league/flysystem-memory` is required: `composer require --dev league/flysystem-memory`.

```yaml
# config/packages/zenstruck_filesystem.yaml

when@test:
    zenstruck_filesystem:
        filesystems:
            public:
                test: 'in-memory:?static'
            private:
                test: 'in-memory:?static'
```

> **Note** the `?static` flag. This ensures the files persist between requests during each test.

#### `RouteFileUrlFeature`

A Symfony-specific `FileUrl` [feature](#features) is available to create `File::url()`'s using a route. This can
be useful for serving private files via a signed/expiring url.

> **Note**: to create expiring urls, [zenstruck/signed-url-bundle](https://github.com/zenstruck/signed-url-bundle)
> is required (`composer require zenstruck/signed-url-bundle`).

First, create a controller to serve your private files:

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Exception\NodeNotFound;
use Zenstruck\Filesystem\Bridge\Symfony\HttpFoundation\FileResponse;
use Zenstruck\SignedUrl\Verifier;
use Zenstruck\SignedUrl\Exception\UrlHasExpired;
use Zenstruck\SignedUrl\Exception\UrlVerificationFailed;

class MyController
{
    /**
     * Standard Symfony signed urls.
     */
    #[Route('/private/file/{path<.+>}', name: 'private_file')]
    public function myAction(string $path, Filesystem $private, UriSigner $signer, Request $request): FileResponse
    {
        if (!$signer->checkRequest($request)) {
            throw new BadRequestHttpException('signing verification failed');
        }

        try {
            $file = $private->file($path);
        } catch (NodeNotFound) {
            throw new NotFoundHttpException('file not found');
        }

        return FileResponse::attachment($file);
    }

    /**
     * Expiring URL support. See
     */
    #[Route('/private/file/{path<.+>}', name: 'private_file')]
    public function myAction(string $path, Filesystem $private, Verifier $verifier, Request $request): FileResponse
    {
        try {
            $verifier->verify($request);
        } catch (UrlHasExpired) {
            throw new BadRequestHttpException('url has expired');
        } catch (UrlVerificationFailed) {
            throw new BadRequestHttpException('signing verification failed');
        }

        try {
            $file = $private->file($path);
        } catch (NodeNotFound) {
            throw new NotFoundHttpException('file not found');
        }

        return FileResponse::attachment($file);
    }
}
```

Next, configure this feature:

```yaml
# config/packages/zenstruck_filesystem.yaml

zenstruck_filesystem:
    filesystems:
        # ...

        private:
            # ...
            route:
                name: private_file
                sign: true
```

Now, when generating URL's for your private filesystem files, they will use this route and sign them:

```php
/** @var \Zenstruck\Filesystem $filesystem */

// create a signed url
$filesystem->file('some/file.txt')->url()->toString(); // "http://localhost/private/file/some/file.txt?_hash=..."

// create a temporary signed url
$filesystem->file('some/file.txt')
    ->url(['expires' => '+30 minutes'])
    ->toString() // "http://localhost/private/file/some/file.txt?_hash=...&_expires=..."
;
```

#### Doctrine Entities

If using the Doctrine ORM, you can add filesystem [`File`](#file)'s as columns on your entity's. This
is done via a custom Doctrine DBAL type and is auto-configured when using the bundle. The File's path
is stored as a string in your database. A Doctrine event subscriber takes care of loading/updating/removing
the filesystem file.

First, add to your entity:

```php
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Filesystem\Node\File;

#[ORM\Entity]
class User
{
    // ...

    #[ORM\Column(type: File::class, nullable: true, options: ['filesystem' => 'public'])]
    public ?File $profileImage = null;
}
```

Usage:

```php
/** @var \Doctrine\ORM\EntityManagerInterface $em */
/** @var \Zenstruck\Filesystem $public */

$user = $em->find(User::class, 1);
$user->profileImage->mimeType() // string - lazily loads the file from the filesystem

$user->profileImage = null;
$em->flush(); // deletes the profile image from the filesystem

$em->remove($user);
$em->flush(); // deletes the profile image from the filesystem after deleting entity
```

##### `PendingFile`

A special `PendingFile` object (extends `File`) is available for creating/updating entity `File`'s.

```php
use Zenstruck\Filesystem\Node\File\PendingFile;

/** @var \Doctrine\ORM\EntityManagerInterface $em */
/** @var \Symfony\Component\HttpFoundation\Request $request */

$user = new User();
$user->profileImage = new PendingFile('/local/filesystem/image.png');

// PendingFile can be created with a Symfony\Component\HttpFoundation\File\UploadedFile
$user->profileImage = new PendingFile($request->files->get('profile_image'));

$em->persist($user);
$em->flush(); // auto saves the pending file to the filesystem

$user->profileImage = new PendingFile('/new/image.jpg');

$em->flush(); // auto-removes old image and saves the new
```

##### Namer's

By default, saving a `PendingFile` names the file with the following template:

```
<original-name-slugified>-<6-random-characters>.<original-extension>
```

This behaviour can be customized with namers that are defined on your column:

```php
use Doctrine\ORM\Mapping as ORM;
use Zenstruck\Filesystem\Node\File;

#[ORM\Entity]
class User
{
    // ...

    // Slugify Namer
    #[ORM\Column(type: File::class, nullable: true, options: ['filesystem' => 'public', 'namer' => 'slugify'])]
    public ?File $profileImage = null; // PendingFile's are saved as "<original-name-slugified>.<original-extension>"

    // Checksum Namer
    #[ORM\Column(type: File::class, nullable: true, options: ['filesystem' => 'public', 'namer' => 'checksum'])]
    public ?File $profileImage = null; // PendingFile's are saved as "<file-contents-checksum>.<original-extension>"

    // Expression Namer
    #[ORM\Column(
        type: File::class,
        nullable: true,
        options: [
            'filesystem' => 'public',
            'namer' => 'expression',
            'expression' => '"foo/bar/{checksum}-{name}{ext}'
        ]
    )]
    public ?File $profileImage = null; // PendingFile's are saved as "foo/bar/<file-contents-checksum>-<original-name-slug>.<original-extension>"

    // ExpressionLanguage Namer
    #[ORM\Column(
        type: File::class,
        nullable: true,
        options: [
            'filesystem' => 'public',
            'namer' => 'expression_language',
            'expression' => '"foo/bar/"~file.checksum()~"-"~name~ext'
        ]
    )]
    public ?File $profileImage = null; // PendingFile's are saved as "foo/bar/<file-contents-checksum>-<original-name-slug>.<original-extension>"
}
```

Alternatively, you can define the _namer_ when creating a `PendingFile`:

```php
use Zenstruck\Filesystem\Node\File\PendingFile;

/** @var \Doctrine\ORM\EntityManagerInterface $em */

$user = new User();
$user->profileImage = new PendingFile('/local/filesystem/image.png', [
    'namer' => 'expression',
    'expression' => '{checksum}-{name}{ext}',
]); // saved as "<file-contents-checksum>-<original-name-slug>.<original-extension>"

$em->persist($user);
$em->flush();
```

Or even with a callback:

```php
use Zenstruck\Filesystem\Node\File\PendingFile;

/** @var \Doctrine\ORM\EntityManagerInterface $em */

$user = new User();
$user->profileImage = new PendingFile('/local/filesystem/image.png', function(PendingFile $file, User $user) {
    return "profile-images/{$user->username}-{$file->checksum()}.{$file->originalExtension()}";
}); // saved as "profile-images/<username>-<file-contents-checksum>.<original-extension>"

$em->persist($user);
$em->flush();
```

#### Full Default Configuration

```yaml
zenstruck_filesystem:

    # Filesystem configurations
    filesystems:

        # Prototype
        name:

        # Filesystem adapter DSN or, if prefixed with "@" filesystem adapter service id
            dsn: ~ # Required
                # Examples:
                # - '%kernel.project_dir%/public/files'
                # - 'ftp://foo:bar@example.com/path'
                # - 's3://accessKeyId:accessKeySecret@bucket/prefix#us-east-1'
                # - '@my_adapter_service'

            # Url prefix or multiple prefixes to use for this filesystem (can be an array)
            url_prefix:

                # Examples:
                - /files
                - 'https://cdn1.example.com'
                - 'https://cdn2.example.com'

            # Service ID of Glide URL builder
            glide_url_builder:    null

            # Route to use for file urls
            route:

                # The route name
                name:                 null

                # Signed?
                sign:                 false

                # Expire the link after x seconds or a relative datetime string (requires zenstruck/signed-url-bundle)
                expires:              null
                    # Examples:
                    # - '1800'
                    # - '+1 day'

                reference_type:       0 # One of 1; 0; 3; 2
                parameters:           []

            # Set to true to create a "readonly" filesystem (write operations will fail)
            readonly:             false

            # Whether or not to log filesystem operations
            log:                  true

            # If false, disable
            # If string, use as filesystem DSN and swap real adapter with this
            # Defaults to false in env's other than "test"
            # If not explicitly configured, in "test" env, defaults to "var/testfiles"
            test:                 null

            # Extra global adapter filesystem config
            config:

                # Example:
                image_check_mime:    true

    # Default filesystem name, if not configured, use first one
    default_filesystem:   null
    doctrine:
        enabled:              true
        events:

            # Whether to register the post-load event listener
            load:
                enabled:              true

                # Whether to load filesystem files by default
                autoload:             true

            # Whether to register the post-persist event listener
            persist:
                enabled:              true

                # Whether to write pending filesystem files on entity persist
                write_on_persist:     true

            # Whether to register the pre/post-update event listeners
            update:
                enabled:              true

                # Whether to save pending filesystem files on entity update
                write_on_update:      true

                # Whether to delete removed filesystem files on entity update
                delete_on_update:     true

            # Whether to register the post-remove event listener
            remove:
                enabled:              true

                # Whether to delete filesystem files on entity remove by default
                delete_on_remove:     true
```
