# Default content deploy
A default content deploy solution for Drupal 8.


- Introduction
- Requirements
- Install
- Configuration
- Drush commands
- Workflow - how to export and deploy content
- Protecting exported content data files
- Maintainers
- Sponsor


# Introduction

This module (DCD) provides content deployment and allows development
and building sites without the need to transfer database between the sites.
The development team can export and deploy all content via Git and content
can be deployed to the staging servers automatically during common deploy
processes. Module provides useful drush commands for export/import content.
Import function can also be ran from administration interface.

# Requirements

## Modules
**Better Normalizers** (better_normalizers) - if you need to deploy files,
  f.e. images, attachments.
https://www.drupal.org/project/better_normalizers

## Sites config synchronization (optional)

If you are sure the export and import sites have the same configuration and you
need to synchronize only content, you can skip this chapter.

You need identical site UUID for successful syncing configuration between sites.
If you need to sychronize configuration, use drush **config-set**
for set Site UUID to identical value.

**Example**

    drush config-set "system.site" uuid 11111111-1111-1111-1111-111111111111

The best practice is to install a drush site from already existing configuration,
e.g.:

    drush si minimal --existing-config


# Install

* with composer

        composer require drupal/default_content_deploy

* with drush

        drush en default_content_deploy

# Configuration

Set DCD content directory in settings.php. We recommend to place directory
outside of the document root.

**Example**

    // Relative path.
    $settings['default_content_deploy_content_directory'] = '../content';
    // Absolute path.
    $settings['default_content_deploy_content_directory'] = '/var/dcd/content';


You can also do this on the `/admin/config/development/dcd` page.

# Drush commands

Module provides many useful shortcuts for export content and one very important
command for deploy content. You can export only one entity, bunch of entities,
entities by type or export whole site at once.

If a wrong content entity type is entered, module displays a list of all content
entity types available on the site as hint.

## drush default-content-deploy:export, drush dcde

Exports a single entity or group of entities with no references.

### Arguments

* **entity_type** - Entity type (e.g. node, user, taxonomy_term,
  custom_entity_type...)

### Options
* **entity_id** - ID of entity for export.
* **bundle** - Entity bundle, e.g. content type for nodes.
* **skip_entities** - ID of entity to skip.
* **force-update** - Deletes configurations files that are not used on the site.
* **folder** - Path to the export folder.

### Examples

    drush dcde node
Export all nodes.

    drush dcde node --folder='../content'
Export all nodes from the specified folder.

    drush dcde node --bundle=page
Export all nodes with bundle (content type) page.

    drush dcde node --bundle=page,article --entity_id=2,3,4
Export all nodes with bundle page or article plus nodes
with entity id 2, 3 and 4.

    drush dcde node --bundle=page,article --skip_entities=5,7
Export all nodes with bundle page or article and skip nodes
with entity id 5 and 7.

    drush dcde node --skip_entities=5,7
Export all nodes and skip nodes with entity id 5 and 7.

## drush default-content-deploy:export-with-references, drush dcder

Exports a single entity or group of entities with all references.

If a wrong content entity type is entered, module displays a list of all content
entity types available on the site as hint.

The options are identical in drush dcde.

### Arguments

* **entity_type** - Entity type (e.g. node, user, taxonomy/term…)

### Options

* **entity_id** - ID of entity for export.
* **bundle** - Entity bundle, e.g. content type for nodes.
* **skip_entities** - ID of entity to skip.
* **force-update** - Deletes configurations files that are not used on the site.
* **folder** - Path to the export folder.

**Examples**

    drush dcder node
Export all nodes with references

    drush dcder node --folder='../content'
Export all nodes with references from the specified folder

    drush dcder node --bundle=page
Export all nodes with references with bundle page

    drush dcder node --bundle=page,article --entity_id=2,3,4
Export all nodes with references with bundle page or article plus nodes
with entitiy id 2, 3 and 4.

    drush dcder node --bundle=page,article --skip_entities=5,7
Export all nodes with references with bundle page or article and skip nodes
with entity id 5 and 7.

    drush dcder node --skip_entities=5,7
Export all nodes and skip nodes with references with entity id 5 and 7.


## drush default-content-deploy:export-site, drush dcdes

Exports a whole site content + path aliases. You can exclude entities
by their type. Use 'drush dcd-entity-list' for list of all content entities
on this system.

### Options

* **skip_entity_type** - entity types to exclude from export.
* **force-update** - Deletes configurations files that are not used on the site.
* **folder** - Path to the export folder.

**Examples**

    drush dcdes
Export complete website.

    drush dcdes --skip_entity_type=node,user
Export complete website but skip nodes and users.

    drush dcdes --folder='../content'
Export complete website but from the specified folder

## drush default-content-deploy:import, drush dcdi

Deploy (import/create/update/replace) content from all exported files.

JSON files with exported content is expected in the directory defined
in **$settings['default_content_deploy_content_directory']** or on the administrative page. It can be defined in the **settings.php**.
See example in the Configuration section above.


### Important rules for import content

- Imported entity is determined by UUID (it can be either new or already
  existing).
- ID of entity is not preserved, so the entity can change its ID.
- Non-existing entity (the new one) is created with a new ID.
- Existing entity is updated only if imported entity is newer
  (by timestamp of the last entity change accross all translations).
- Imported entity with the same or older time
  than the current existing entity is skipped.
- If a file entity does not have an existing file, the file will be created.
  The file will be recreated even if there is an existing file entity,
  but its file has been deleted.
- This behavior can be changed by parameter *--force-update*
  or *--force-override*. See an Example section.

***drush dcdi --force-override***

- All existing content will be overridden. Locally updated default content
will be reverted to the state defined in a content directory.

***drush dcdi --folder***

- All content will be imported from this folder.

***drush dcdi --verbose***

- Print detailed information about importing entities.

**Examples**

    drush dcdi
    drush dcdi --folder='../content'
    drush dcdi --force-override
    drush dcdi --force-override --folder='../content'

## drush default-content-deploy:uuid-info, drush dcd-uuid-info

Display UUID value of Entity.

**Example**

    drush dcd-uuid-info node 1


## drush default-content-deploy:entity-list, drush dcd-entity-list

Displays all current content entity types.

**Example**

    drush dcd-entity-list

# GUI

## Settings
Go to the `/admin/config/development/dcd` page.

## Export
Go to the `/admin/config/development/dcd/export` page.

## Import
Go to the `/admin/config/development/dcd/import` page.


# Team workflow - how to synchronize configuration and content between sites

Even though the DCD module does not necessarily require that the sites have
identical site UUID, it is logical that transferring entities from one site to
another requires identical configuration of entities (e.g. identical fields in
identically named bundles). That is why we include configuration synchronization
to the teamworkflow.

There are many articles about export/import configuration, so this is only a
summarization of the most important things.

## Initiate project and Synchronize site configurations

**Developer 1 (first pusher)**

provides the first copy of a project. He must:

1. Create Drupal 8 project
2. Set identical information for the whole development team
   (e.g. save them to default.settings.php)
  1. Set identical directory for config management ($config_directories['sync'])
  2. Set identical directory for content export/import ($config['content'])
  3. Set identical file or value for Drupal salt ($settings['hash_salt']), e.g.:

         $settings['hash_salt'] = file_get_contents('../config/salt/salt.txt');

3. Install Drupal project (use the same installation profile, e.g. minimal)
4. Set (or get) Site UUID and share the value with the team.
   Identical Site UUID is neccessary for sharing Drupal configuration files.

        drush config-get "system.site" uuid

        drush config-set "system.site" uuid "112233…8899"

5. Install **Default Content Deploy** module and optionally the **File entity**
   module for exporting files like images.
6. Export configuration **drush cex**
7. Prepare team Git repository and push the project files into it.
8. Share necessary information with other developers.


**Developer 2 - n-th (pullers)**

install clones of the project. They must:

1. Clone project from the Git repository
2. Set identical information (identical setting in settings.php)
  1. Common directory for config management ($config_directories['sync'])
  2. Common directory for content export/import ($settings['default_content_deploy_content_directory'])
  3. Common file or value for Drupal salt ($settings['hash_salt'])
3. Install Drupal with the same installation profile
4. Set Site UUID to identical value.

        drush config-set "system.site" uuid "112233…8899"

5. Import configuration

        drush cim


## Export and import content

For successful syncing content between sites, you need to have identical UUIDs
for Admin user and Anonymous user (and for others users too if you have them).
These values will be synced automatically during DCD import process if the user
entities are exported, so we recommend to start exporting content with all
references.

**Developer pusher**

1. Creates some testing content
2. Export content. There are many ways to export content via drush,
   from one entity to the entire site (the easiest way).

        drush dcdes

3. Commit and push exported files to common Git repository

        git commit
        git push

**Developer puller**

1. Pull changes from the git repository

        git pull

2. Simply import content

        drush dcdi

  This command warns a user about entities in conflict.
  You can use **--verbose** option (**-v**) for more detailed information.
  We recomend more aggressive way which ensures that all content entities
  will be synchronized with the source. This option also rewrites already
  created content and updates UUID for core user entity. See detailed
  explanation in the **Important Import rules**.

## Common team workflow

**Developer pusher**

    1. drush cex
    2. drush dcdes
    3. git commit
    4. git push

**Developer puller**

    1. git pull
    2. drush updb
    4. drush cim
    5. drush dcdi


## Jenkins and tools for continous development / integration

Imagine Jenkins like one of the developers in the puller role but with
no interaction capability. His workflow can look like this:

    1. git pull
    2. drush updb -y
    3. drush cim -y
    4. drush cr
    5. drush dcdi -y


# Protecting exported content data files

We recommend placing the content directory outside of the document root,
so the exported content is well protected. In this case it will not be
accessible from the web server.

If this is not possible, it could result in a security problem.
Should any attacker know the UUID of desired content and also where it is
stored, then he could determine the URL to obtain the desired content without
permission.

Example of attacker’s URL:

    http://example.com/dcd_content_dir/node/uuid.json

**Secure content directory within docroot**

You can secure access to content directory via .htaccess or Nginx configuration.

Example for Nginx host config:

    location ~ .*/default_content_deploy_content/.*.json$ {
      return 403;
    }

Example of .htaccess for Apache host.

    Deny from all

Place .htaccess into content directory.


# Authors and maintainers
- Martin Klíma, https://www.drupal.org/u/martin_klima, martin.klima@hqis.cz
- Jakub Hnilička, https://www.drupal.org/u/hnilickajakub
- Radoslav Terezka, https://www.drupal.org/u/hideaway
- Miroslav Lee, https://www.drupal.org/u/miroslav-lee
- Markus Kalkbrenner, https://www.drupal.org/u/mkalkbrenner
-
# Sponsor
This project has been sponsored by:

HBF s.r.o., http://hbf.sk/, https://www.drupal.org/hbf

HBF provides flexible easy-to-use web solutions for your company.
Its mission is to help you run your business in online world with attractive
and perfectly working website.
