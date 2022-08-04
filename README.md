# zenstruck/filesystem

This library is a wrapper for the excellent [league/flysystem](https://flysystem.thephpleague.com/docs/)
_File Storage Abstraction_ library. It provides an _alternate_ [API](#api) including some of the following
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
$filesystem->write('some/file.txt', '/path/to/local/file.txt');

// modify an exising file using a "real file"
$filesystem->write('existing/file.csv', function(\SplFileInfo $file) {
    $some3rdPartyService->manipulate($file);

    return $file;
}); // the file returned from the callback is written to "exising/file.csv"
```

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
```

### Nodes

#### `File`

#### `Image`

#### `Directory`

## Filesystems

### `AdapterFilesystem`

#### Flysystem Adapters

##### `LocalAdapter`

##### `StaticInMemoryAdapter`

#### Features

##### `PrefixFileUrlFeature`

### `MultiFilesystem`

### `ReadonlyFilesystem`

### `LoggableFilesystem`

### `ArchiveFile`

### `TestFilesystem`

#### `InteractsWithFilesystem`

_(Note about `TestFilesystemProvider|FixtureFilesystemProvider`)_

## Doctrine Integration

## Symfony Integration

### Responses

### Validators

### Symfony Bundle

#### Configuration

##### `RouteFileUrlFeature`

#### Services

#### Doctrine Entities

##### `PendingFile` Namers

#### Testing

_(Note about `FixtureFilesystemProvider`)_
_(Note about performance improvements using (static) in-memory adapters)_
_(Note about disabling clearing test filesystems before each test if using (static) in-memory adapters)_

#### Full Default Configuration
