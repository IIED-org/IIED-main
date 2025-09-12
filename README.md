```
 ______ ______ ________ _______                               __          
|      \      \        \       \                             |  \         
 \▓▓▓▓▓▓\▓▓▓▓▓▓ ▓▓▓▓▓▓▓▓ ▓▓▓▓▓▓▓\       ______ ____   ______  \▓▓_______  
  | ▓▓   | ▓▓ | ▓▓__   | ▓▓  | ▓▓______|      \    \ |      \|  \       \ 
  | ▓▓   | ▓▓ | ▓▓  \  | ▓▓  | ▓▓      \ ▓▓▓▓▓▓\▓▓▓▓\ \▓▓▓▓▓▓\ ▓▓ ▓▓▓▓▓▓▓\
  | ▓▓   | ▓▓ | ▓▓▓▓▓  | ▓▓  | ▓▓\▓▓▓▓▓▓ ▓▓ | ▓▓ | ▓▓/      ▓▓ ▓▓ ▓▓  | ▓▓
 _| ▓▓_ _| ▓▓_| ▓▓_____| ▓▓__/ ▓▓      | ▓▓ | ▓▓ | ▓▓  ▓▓▓▓▓▓▓ ▓▓ ▓▓  | ▓▓
|   ▓▓ \   ▓▓ \ ▓▓     \ ▓▓    ▓▓      | ▓▓ | ▓▓ | ▓▓\▓▓    ▓▓ ▓▓ ▓▓  | ▓▓
 \▓▓▓▓▓▓\▓▓▓▓▓▓\▓▓▓▓▓▓▓▓\▓▓▓▓▓▓▓        \▓▓  \▓▓  \▓▓ \▓▓▓▓▓▓▓\▓▓\▓▓   \▓▓
                                                                          
```

## Issue management
New issues should be associated with the [IIED-main project](https://github.com/orgs/IIED-org/projects/2/views/2). Status columns are as follows:

- **Backlog**: Issues go here when initially created. Assign the issue to the project and tag it with the relevant milestone.
- **Todo**: Issues go into this column when prioritised and optionally assigned
- **In progress**: When the issue has been assigned and work commenced it moves here. The number of items should ideally be limited ([Kanban](https://www.atlassian.com/agile/kanban/wip-limits) methodology) or addressed by means of sprints ([Scrum](https://scrumguides.org/scrum-guide.html)).
- **Ready for test**: Issued assigned to someone else to check work is complete
- **Done**: Issue is closed

## Local developer setup with ddev

This repository comes with a `.ddev/config.yaml` file which will help to set up locally using ddev and Docker.

## Requirements

To run locally, you will need [Docker](https://www.docker.com/products/docker-desktop/) (or OrbStack) and [ddev](https://ddev.com/get-started/).

## Setup

1. Clone this repo, move into the directory that contains the codebase:

```
git clone git@github.com:IIED-org/IIED-main.git IIED-main
cd IIED-main
```

2. Start Docker Desktop and run `ddev start` to build the docker containers. Note that if you have any Lando containers running you will need to stop them.

3. Download a copy of last night's production database via Jenkins and save it to the project root. Import it using the following command:

```
ddev import-db -f iied-prod-yyyymmdd.sql.gz
ddev drush cr
```

4. To modify the theme, including tailwind.pcss and twig templates, move into the theme directory, install the necessary node modules, and run the watch command:

```
cd docroot/themes/custom/iied_tw
ddev npm install
ddev npm run watch
```

Open https://iied-main.ddev.site:3000 to view live changes in the browser. When ready to deploy, build a minimised (dist) version:

```
ddev npm run build
```

5. Composer updates: run `ddev composer update --dry-run` to see what updates are available. Ensure you're on an issue branch before running the command for real, i.e. without the `--dry-run` flag.

6. Config split

We use the Config Split module to separate configuration intended for
specific environments. We have splits for local, dev, stage and live.
In the docroot/sites/default/settings.ddev.php file we include the
following:

```
/**
 * Use "local" config split for development
 */
$config['config_split.config_split.local']['status'] = TRUE;
$config['config_split.config_split.dev']['status'] = FALSE;
$config['config_split.config_split.stage']['status'] = FALSE;
$config['config_split.config_split.prod']['status'] = FALSE;
```

This set the 'local' split to active and the 'live' and 'dev' to inactive. With
this setup, running `ddev drush cr` then `ddev drush cim` will import the local split configuration as well as the default configuration. In our case, this will
enable other modules useful for developers like devel and stage_file_proxy.

To enable a module on the current (probably local) split and not have it enabled
by default. As an example, we'll try this with the help_topics module.

Enable the module

```
ddev drush en help_topics
```

Then add it to the complete split at:

https://iied-main.lndo.site/admin/config/development/configuration/config-split/local/edit

Clear the cache and export the config.

```
ddev drush cr
ddev drush cex
```

A git diff will show that the config/default/config_split.config_split.local.yml
file has been updated to include the module, rather than the core.extensions.yml

```
$ git diff
diff --git a/config/default/config_split.config_split.local.yml b/config/default/config_split.config_split.local.yml
index acadb2a47..1ff8d48de 100644
--- a/config/default/config_split.config_split.local.yml
+++ b/config/default/config_split.config_split.local.yml
@@ -10,6 +10,7 @@ module:
   config_devel: 0
   devel: 0
   devel_generate: 0
+  help_topics: 0
   migrate_devel: 0
   stage_file_proxy: 0
   views_ui: 0
```

This should result in the module being enabled only when the local split is
active.

7. Common drush commands.

Generate a one time login link:

```
ddev drush uli
```

Clear the cache:

```
ddev drush cr
```

Inspect the ddev configuration:

```
ddev describe
```

