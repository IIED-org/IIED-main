# IIED Drupal 7 migrations

This module provides custom migrations from the Drupal 7 IIED.org site into the
Drupal 9 site.

Currently (December 2021) the Drupal 9 site is known as the 'Pubs' site and is at
https://pubs.iied.org/. In the future, this will be consolidated as a single
site at https://www.iied.org/.

## Drupal 7 migration setup - source files

Before running migrations with files and images, we need to have the source
files accessible to the migration scripts.

I have a structure like this:

```bash
~/sites/l.iied/iied-main    [this Git respository]
~/sites/l.iied/iied-d7      [the Drupal 7 Git repository]
```

This allows the .lando.yml file in this repository to map the docroot of the
Drupal 7 site to a volume to provide access to the source files.

```bash
      volumes:
        - ../iied-d7/docroot/:/app/d7-files
```

This presents the Drupal 7 docroot at /app/df-files for the migration scripts to
be able to find the images and files to migrate.

## Drupal 7 migration setup - source database

The .lando.yml file also includes definition of a second database server for the
Drupal 7 source data to help with migration development and testing.

To import a database into the d7db host, you can use lando db-import.

For example:

```bash
lando db-import --host d7db acquia.2021-11-16-1637096083.sql
```

With this module enabled, you can run drush migrate-status to see the
migrations.

```bash
lando drush ms
```

Assuming you have the database configured and data imported, `lando drush ms`
should give something like this:

```bash
-------------------------------------------- --------------------------------------------------- -------- ------- ---------- ------------- ---------------------
  Group                                        Migration ID                                        Status   Total   Imported   Unprocessed   Last Imported
 -------------------------------------------- --------------------------------------------------- -------- ------- ---------- ------------- ---------------------
  IIED Drupal 7 Migrations (iied_migrate_d7)   iied_d7_files                                       Idle     19471   0          19471
  IIED Drupal 7 Migrations (iied_migrate_d7)   iied_d7_media_documents                             Idle     179     0          179
  IIED Drupal 7 Migrations (iied_migrate_d7)   iied_d7_media_images                                Idle     17385   0          17385
  IIED Drupal 7 Migrations (iied_migrate_d7)   iied_d7_project_para_additional_elements            Idle     20      0          20
  IIED Drupal 7 Migrations (iied_migrate_d7)   iied_d7_project_para_full_width_media               Idle     4       0          4
  IIED Drupal 7 Migrations (iied_migrate_d7)   iied_d7_projects                                    Idle     292     0          292
```

I suggest running the migrations one at a time, in order.

```bash
lando drush mim iied_d7_files
lando drush mim iied_d7_media_documents
...
```

You can try running the whole group with:

```bash
lando drush mim --group=iied_migrate_d7
```
