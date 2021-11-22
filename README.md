```
██████╗ ██╗   ██╗██████╗ ███████╗    ██████╗ ███████╗██╗   ██╗
██╔══██╗██║   ██║██╔══██╗██╔════╝    ██╔══██╗██╔════╝██║   ██║
██████╔╝██║   ██║██████╔╝███████╗    ██║  ██║█████╗  ██║   ██║
██╔═══╝ ██║   ██║██╔══██╗╚════██║    ██║  ██║██╔══╝  ╚██╗ ██╔╝
██║     ╚██████╔╝██████╔╝███████║    ██████╔╝███████╗ ╚████╔╝
╚═╝      ╚═════╝ ╚═════╝ ╚══════╝    ╚═════╝ ╚══════╝  ╚═══╝
```

## Local developer setup with Lando

This repositrory comes with a .lano.yml file which will help to set up locally
using Lando, Docker and Acquia command line acli.

## Requirements

To run locally, you will need Lando and Docker.
Note that the macOS and Windows Lando installer will install Docker for you if
needed. Please check the relevent documentation.

 - Lando https://docs.lando.dev/basics/installation.html
 - Docker https://docs.lando.dev/basics/installation.html#system-requirements

## Setup

1. Clone this repo and move into the directory that contains the codebase.

```
git clone git@github.com:IIED-org/pubs.git gitroot
cd gitroot
```

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
lado acli pull:database
```
This currently the .acquia-cli.yml to connect to the IIED Pubs cloud app. It
will ask which environment to pull the database from.
Select the dev database: [0].

```
Using Cloud Application IIED Pubs

 Choose a Cloud Platform environment [Dev, dev (vcs: dev-master)]:
  [0] Dev, dev (vcs: dev-master)
  [1] Prod, prod (vcs: tags/2021-11-02)
  [2] Stage, test (vcs: tags/2021-11-02)
> 0
```

4. Crete settings.local.php

To override certain settings and configuration we can use settings.local.php.
To do so, copy the example.settings.local.php file from the sites folder to
sites/default/settings.local.php. This will include some recommended defaults.

```
cp docroot/sites/example.settings.local.php  docroot/sites/default/settings.local.php
```

5. Enable and configure stage_file_proxy

We should now be able to use drush to run commands in the appserver container.
To avoid having to copy all files locally, stage_file_proxy can be enabled and
configured.

```
lando drush en stage_file_proxy
```

Now add the the following line to the end of the settings.local.php file.

```
$config['stage_file_proxy.settings']['origin']  = 'https://pubs.iied.org';
```

6. Common drush commands.

Generate a one time login link:

```
lando drush uli
```

Clear the cache:

```
lando drush cr
'''

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
