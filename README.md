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
3. [`ArchiveFile`](#archivefile) representing a local zip file that acts as both a filesystem _and_ a real file.
4. [Doctrine Integration](#doctrine-integration).
5. [Symfony Integration](#symfony-integration)
    - [Custom Responses](#responses)
    - [Validators](#validators)
    - [Bundle](#symfony-bundle) to help configure filesystem services, wire the Doctrine integration and additional
      testing helpers.

## Installation

```bash
composer require zenstruck/filesystem
```

## Backward Compatibility Promise

This library follows [Symfony's BC Promise](https://symfony.com/doc/current/contributing/code/bc.html) with the
following exceptions:
1. `Zenstruck/Filesystem` and any implementations are considered _internal_ for _implementation_/_extension_.
2. `Zenstruck/Filesystem/Node` and any implementations are considered _internal_ for _implementation_/_extension_.
