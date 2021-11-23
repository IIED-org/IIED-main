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
To do so, copy the sites/default/iied.example.settings.local.php file to
sites/default/settings.local.php. This will include some recommended defaults.

```
cp docroot/sites/default/iied.example.settings.local.php  docroot/sites/default/settings.local.php
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

6. Enable and configure stage_file_proxy

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











