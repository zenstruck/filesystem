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

## Symfony Integration

### Full Default Bundle Config

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
