INTRODUCTION
------------
The migrate_pubs module is based on migrate_example (part of migrate_plus) and
contains a single migration which brings in the latest Project content from www.iied.org.

THE MIGRATION
--------------

migrate_plus.migration.iied_projects.yml

RUNNING THE MIGRATION
---------------------

drush mim iied_projects --update
