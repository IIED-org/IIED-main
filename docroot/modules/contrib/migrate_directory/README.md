# Migrate Directory

When migrating content from a non-Drupal site such as MODx or from an export utility such as Tumblr Utils, you will need a mechanism to import a directory of files into Drupal as managed files.

This module provides a source plugin that allows you to recurse through an entire directory in a migration:
 
```yaml
source:
  plugin: directory
  path: /path/to/files/to/migrate
```

## Specifying a file pattern

If your directory contains mixed content, you can use `pattern` to specify a file name pattern, including the extension:

```yaml
source:
  plugin: directory
  path: /path/to/files/to/migrate
  pattern: '/.*\.(png|jpg|bmp|gif)/i'
```

## Fields

The source plugin provides several fields:

`path`

The path to the file, without the filename.

`relative_path`

The path to the file, relative to the `path` argument given in the `source` definition. This is used for replicating the same directory structure when migrating the file.

`absolute_path`
 
The absolute path to the file, resolving any symlinks.

`filename`
 
The file name.

`basename`
 
Same as `filename`, but also resolves some special cases.
 
`extension`
 
The file extension, if any. 

## Installation

Install like any other Drupal module. Either:

Download the archive from Drupal.org, and unpack in your `modules/` directory.

Or, use composer to install:

```shell
composer require drupal/migrate_directory
```

## Use

Create a new file migration using the migration template in the file module from core.

### Flattening the target directory tree

This example will migrate the files, flattening out the directory structure from the source:

```yaml
id: my_image_migration
label: 'My Image Migration'
source:
  plugin: directory
  path: /path/to/files/to/migrate
  constants:
    dest_prefix: 'public://migrate/files/'
process:
  _source_file_path:
    -
      plugin: urlencode
      source: path
  _dest_file_path:
    -
      plugin: concat
      source:
        - constants/dest_prefix
        - basename
    -
      plugin: urlencode
  uri:
    plugin: file_copy
    source:
      - '@_source_file_path'
      - '@_dest_file_path'
  status:
    -
      plugin: default_value
      default_value: 1
  _timestamp:
    -
      plugin: callback
      callable: time
  created: '@_timestamp'
  changed: '@_timestamp'
  uid:
    -
      plugin: default_value
      default_value: 0
destination:
  plugin: 'entity:file'
migration_dependencies: {  }

```

### Preserving the directory structure

Alternatively, you could choose to preserve it by using the `relative_path` source property, instead of `basename`:

```yaml
id: my_image_migration
label: 'My Image Migration'
source:
  plugin: directory
  path: /path/to/files/to/migrate
  constants:
    dest_prefix: 'public://migrate/images/'
process:
  _source_file_path:
    -
      plugin: urlencode
      source: path
  _dest_file_path:
    -
      plugin: concat
      source:
        - constants/dest_prefix
        - relative_path
    -
      plugin: urlencode
  uri:
    plugin: file_copy
    source:
      - '@_source_file_path'
      - '@_dest_file_path'
  status:
    -
      plugin: default_value
      default_value: 1
  _timestamp:
    -
      plugin: callback
      callable: time
  created: '@_timestamp'
  changed: '@_timestamp'
  uid:
    -
      plugin: default_value
      default_value: 0
destination:
  plugin: 'entity:file'
migration_dependencies: {  }
```

