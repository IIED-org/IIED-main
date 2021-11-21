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










