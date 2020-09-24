INTRODUCTION
------------
The migrate_pubs module is based on migrate_example (part of migrate_plus) and
contains a series of separate migrations for bringing in data from the legacy
MySQL database that powers the pubs.iied.org website.

THE SOURCE PUBS SITE
-------------

A copy of the files and database from pubs.iied.org to pubscopy.iied.org was
made in late 2019. Subsequently the live database has been copied to pubscopy
on an adhoc basis (via mysqldump). A database connection to the pubscopy site is
specified in this site's settings.php, and requires a ssh tunnel connection with
local port mapping to work, e.g.:

ssh -L 3307:127.0.0.1:3306 -i "~/.ssh/ec2_iied_4.pem" centos@pubscopy.iied.org -f -N

A connection to the live (pubs.iied.org) site should be made when running the
migrations "for real".

STRUCTURE
---------
There are two primary components to the migrations:

  1. Migration configuration.
   Files in the config/install directory provide migration configuration as
   configuration entities, and have names of the form
   migrate_plus.migration.<migration ID>.yml

   While developing, to get edits to the .yml files in config/install to
   take effect in active configuration, use the following command:

   drush cim -y --partial --source=modules/custom/migrate_pubs/config/install

2. Source plugins, in src/Plugin/migrate/source. These are referenced from the
   configuration files, and provide the source data to the migration processing
   pipeline, as well as manipulating that data where necessary to put it into
   a canonical form for migrations.

THE MIGRATIONS
--------------
The YAML and PHP files are documented in-line. The
migrate_plus.migration_group.pubs.yml file describes the order in which the
migrations are to be run.

migrate_plus.migration.iied_projects.yml
migrate_plus.migration.iied_tags.yml
migrate_plus.migration.pubs_orgs.yml
migrate_plus.migration.pubs_authors.yml
migrate_plus.migration.pubs_doctypes.yml
migrate_plus.migration.pubs_node.yml
migrate_plus.migration.pubs_pdfs.yml
migrate_plus.migration.pubs_projects.yml
migrate_plus.migration.pubs_series.yml
migrate_plus.migration.pubs_themes.yml

RUNNING THE MIGRATIONS
----------------------
The migrate_tools module (https://www.drupal.org/project/migrate_tools) provides
the tools you need to perform migration processes. At this time, the web UI only
provides status information - to perform migration operations, you need to use
the drush commands.

# Enable the tools and the example module if you haven't already.

drush en -y migrate_tools,migrate_example

# Look at the migrations. They are displayed in the order they will be run,
# which reflects their dependencies.

drush ms                    # Abbreviation for migrate-status

# as at 2020-09-24T15:35:00+01:00, pubs live connection
----------------------------- --------------- -------- ------- ---------- ------------- ---------------
 Group                         Migration ID    Status   Total   Imported   Unprocessed   Last Imported
----------------------------- --------------- -------- ------- ---------- ------------- ---------------
 Pubs library imports (pubs)   iied_projects   Idle     261     0          261
 Pubs library imports (pubs)   iied_tags       Idle     907     0          907
 Pubs library imports (pubs)   pubs_authors    Idle     6332    0          6332
 Pubs library imports (pubs)   pubs_doctypes   Idle     40      0          40
 Pubs library imports (pubs)   pubs_themes     Idle     23      0          23
 Pubs library imports (pubs)   pubs_orgs       Idle     236     0          236
 Pubs library imports (pubs)   pubs_projects   Idle     276     0          276
 Pubs library imports (pubs)   pubs_series     Idle     57      0          57
 Pubs library imports (pubs)   pubs_node       Idle     7629    0          7629
----------------------------- --------------- -------- ------- ---------- ------------- ---------------

# Run the import operation for all the pubs migrations.

drush mim --group=pubs      # Abbreviation for migrate-import

# Look at what you've done! Also, visit the site and see the imported content

drush ms

http://pubs.dd:8083/table   # Your local URL may vary

# Run the rollback operation for all the migrations (removing all the imported
# content.). Note that it will rollback the migrations in the opposite order as
# they were imported.

drush mr --group=pubs       # Abbreviation for migrate-rollback




TESTING
-------
Each migration has been tested separately with either full or sample data
imports.

# You can import specific migrations to further test and update content.

drush mim iied_projects,pubs_projects

# At this point, look at your content listing. You can rollback specific migrations

drush mr iied_projects,pubs_projects
