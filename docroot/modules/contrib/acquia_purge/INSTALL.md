# Installation

The Acquia Purge module provides integration with the
[Purge module](https://www.drupal.org/project/purge) and makes it extremely
simple to achieve accurate, efficient cache invalidation on your Acquia Cloud
environment.

Setting it all up shouldn't take long:

1. Download and enable the required modules:

   ```
   drush en acquia_purge --yes
   ```

2. Also enable the necessary Purge components:

   ```
   drush en purge_drush purge_queuer_coretags \
   purge_processor_lateruntime purge_processor_cron purge_ui  --yes
   ```

3. Add the "`Acquia Cloud`" purger to your configuration:

   ```
   drush p:purger-add --if-not-exists acquia_purge
   ```

4. To invalidate your _Acquia Platform CDN_ subscription, add a second purger:

   ```
   drush p:purger-add --if-not-exists acquia_platform_cdn
   ```
   ```
   drush p:purger-ls|grep acquia_platform_cdn
     f0eed62e59   acquia_platform_cdn   Acquia Platform CDN
   ```
   ```
   drush p:purger-mvu f0eed62e59
   ```
   ```
   drush p:purger-ls
    ------------ --------------------- ----------------------------
     Instance     Plugin                Label
    ------------ --------------------- ----------------------------
     f0eed62e59   acquia_platform_cdn   Acquia Platform CDN
     bcddfb627d   acquia_purge          Acquia Cloud
    ------------ --------------------- ----------------------------
   ```

5. Verify if purge reports that your setup is working:
   ```
   drush p:diagnostics --fields=title,severity
    ------------------------------ ----------
     Title                          Severity
    ------------------------------ ----------
     Acquia Purge Recommendations   OK
     Acquia Platform CDN            OK
     Acquia Cloud                   OK
     Queuers                        OK
     Page cache max age             OK
     Page cache                     OK
     Purgers                        OK
     Capacity                       OK
     Queue size                     OK
     Processors                     OK
    ------------------------------ ----------
   ```

### Tuning

By strict design and principle, this module doesn't have any UI exposed settings
or configuration forms. The reason behind this philosophy is that - as a pure -
utility module only site administrators should be able to change anything and if
they do, things should be traceable in ``settings.php``. Although Acquia Purge
attempts to stay as turnkey and zeroconf as possible, the following options
exist as of this version and documented below:

```
╔══════════════════════════╦═══════╦═══════════════════════════════════════════╗
║      $settings key       ║ Deflt ║               Description                 ║
╠══════════════════════════╬═══════╬═══════════════════════════════════════════╣
║ acquia_purge_token       ║ FALSE ║ If set, this allows you to set a custom   ║
║                          ║       ║ X-Acquia-Purge header value. This helps   ║
║                          ║       ║ offset DDOS style attacks but requires    ║
║                          ║       ║ balancer level configuration chances for  ║
║                          ║       ║ you need to contact Acquia Support.       ║
║                          ║       ║ $settings['acquia_purge_token'] = 'secret'║
╚══════════════════════════╩═══════╩═══════════════════════════════════════════╝
```
