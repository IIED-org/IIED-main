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
Issues should be associated with the [IIED-main project](https://github.com/orgs/IIED-org/projects/2/views/2). Status columns are as follows:

- **Backlog**: Issues go here when initially created
- **Todo**: Issues go into this column when prioritised and optionally assigned
- **In progress**: When the issue has been assigned and work commenced it moves here. The number of items in this column should be no more than the WIP limit.
- **Ready for testing**: Issued assigned to someone else to check work is complete
- **Done**: Issue is closed

There are two [milestones ](https://github.com/IIED-org/pubs/milestones)
* [Site freeze](https://github.com/IIED-org/pubs/milestone/1), which should contain all the pre-requisites in the [Teams channel](https://teams.microsoft.com/l/entity/com.microsoft.teamspace.tab.planner/_djb2_msteams_prefix_2584532485?context=%7B%22subEntityId%22%3Anull%2C%22channelId%22%3A%2219%3A4c87f5ca830a468780a27d73d3024b0c%40thread.skype%22%7D&groupId=662a2f37-a62f-4018-99ac-faea87204980&tenantId=d5c06f4c-c977-41f9-9baa-6c4050719973) and [Site freeze to launch document](https://iied.sharepoint.com/:w:/s/WebTeam/EfX3W_u4pAlCn1Qkq6diPa4BSJ-8qP7jpJ9v7fExJrOcOw?e=MfaJkd).  
* [Soft launch](https://github.com/IIED-org/pubs/milestone/2), which is yet to be fully defined

## Local developer setup with Lando

This repository comes with a .lando.yml file which will help to set up locally
using Lando, Docker and Acquia command line acli.

## Requirements

To run locally, you will need Lando and Docker.
Note that the macOS and Windows Lando installer will install Docker for you if
needed. Please check the relevent documentation.

 - Lando https://docs.lando.dev/basics/installation.html
 - Docker https://docs.lando.dev/basics/installation.html#system-requirements

## Setup

1. Clone this repo, move into the directory that contains the codebase and connect to Acquia git remote.

```
git clone git@github.com:IIED-org/pubs.git IIED-main
cd IIED-main
git remote add ac <acquiaGitPath>
```

To determine the <acquiaGitPath>, navigate to the application overview page via https://cloud.acquia.com/a/applications, select “View Git Information” from the Actions menu and copy the git URL, e.g. irforum@svn-6076.devcloud.hosting.acquia.com:irforum.git

2. Run lando start to build the docker contaiers.

```
lando start
```
3. Import a copy of the database.

The 'acquia' Lando recipe includes the Acquia Command Line utility. This can be
run with `lando acli`.

This repo also includes an .acquia-cli.yml file which defines which cloud
instance to connect to.

To pull a copy of the database we can run the following command.

```
lando acli pull:database
```
This references .acquia-cli.yml to connect to the IIED Pubs cloud app. It
will ask which environment to pull the database from. Follow the prompts to set up credentials for accessing the Acquia server as necessary.

Select the prod database: [1].

```
Using Cloud Application IIED Pubs

 Choose a Cloud Platform environment [Dev, dev (vcs: dev-master)]:
  [0] Dev, dev (vcs: dev-master)
  [1] Prod, prod (vcs: tags/2021-11-02)
  [2] Stage, test (vcs: tags/2021-11-02)
> 0
```

4. Create settings.local.php

To override certain settings and configuration we can use settings.local.php.
To do so, copy the sites/default/iied.example.settings.local.php file to
sites/default/settings.local.php. This will include some recommended defaults.

```
cp docroot/sites/default/iied.settings.local.php  docroot/sites/default/settings.local.php
```

5. Config split

We are using the Config Split module to separate configuration intended for
specific environments. We have splits for local, dev and live.
In the docroot/sites/default/iied.example.settings.local.php file we include the
following:

```
/**
 * Use "local" config split
 */
$config['config_split.config_split.live']['status'] = FALSE;
$config['config_split.config_split.dev']['status'] = FALSE;
$config['config_split.config_split.local']['status'] = TRUE;
```

This set the 'local' split to active and the 'live' and 'dev' to inactive. With
this setup, running `drush cr` then `drush cim` will import the local split
configuration as well as the default configuration. In our case, this will
enable other modules useful for developers like devel and stage_file_proxy.

To enable a module on the current (probably local) split and not have it enabled
by default. As an example, we'll try this with the help_topics module.

Enable the module

```
lando drush en help_topics
```

Then add it to the complete split at:

https://iied-main.lndo.site/admin/config/development/configuration/config-split/local/edit

Clear the cache and export the config.

```
lando drush cr
lando drush cex
```

A git diff will show that the config/default/config_split.config_split.local.yml
file has been updated to inclide the module, rather than the core.extensions.yml

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

This should result in the modue being enabled only when the local split is
active.

6. Enable and configure stage_file_proxy

To avoid having to copy all files locally, stage_file_proxy can be enabled and
configured.

```
lando drush en stage_file_proxy
```

Now add the the following line to the end of the settings.local.php file.

```
$config['stage_file_proxy.settings']['origin']  = 'https://pubs.iied.org';
```

7. Common drush commands.

Generate a one time login link:

```
lando drush uli
```

Clear the cache:

```
lando drush cr
```

Follow the appserver logs:
```
lando logs -s appserver --follow
```

Inspect the lando configuration:

```
lando info
```

## Drupal 7 migration setup

The .lando.yml file includes definition for a second database server for the
Drupal 7 source data to help with migration development and testing.

To import a database into the d7db host, use lando db-import.

For example:

```
lando db-import --host d7db acquia.2021-11-16-1637096083.sql
```
