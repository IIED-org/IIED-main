# INTRODUCTION
Manually purge cached resources for Cloudflare from Drupal site uses Guzzle HTTP
 client instead of cURL.

This module convert This CURL command to Guzzle HTTP.
curl -X POST "https://api.cloudflare.com/client/v4/zones/
023e105f4ecef8ad9ca31a8372d0c353/purge_cache" \
     -H "X-Auth-Email: user@example.com" \
     -H "X-Auth-Key: c2547eb745079dac9320b638f5e225cf483cc5cfdda41" \
     -H "Content-Type: application/json" \
     --data '{"purge_everything":true}'

It provides any Drupal user with permissions to manually purge Cloudflare cache.

When can I use this module?
You can use this module in two possible scenarios:

You activate Cloudflare through https://www.cloudflare.com/partners Certified
Cloudflare Hosting Partner, but you don't have the Cloudflare module and you
 can't purge Cloudflare cache from your Drupal site.
You activate cloudflare through the Clouldflare module and you can't install the
 purge module for whatever reason. In this case the Cloudflare purger module
 can't be enabled and you will need some way to clear Cloudflare cache.

How is Cloudflare Purge secure?
It uses custom CSRF token in Drupal.  It also uses Guzzle HTTP client request
for all API calls. So it's not vulnerable to CSRF exploits and avoids security r
isk.


# REQUIREMENTS
It only works with Drupal sites use Cloudflare.
You must have the Cloudflare Zone ID and Authorization (X-AUTH-KEY).
Zone ID: How to Get a Zone ID?
https://api.cloudflare.com/#getting-started-resource-ids
X-Auth-Key or Authorization: API key generated in Cloudflare on the "My Account"
 page. https://api.cloudflare.com/#getting-started-requests
Permission needed in CF: #cache_purge:edit

# INSTALLATION
Install as any other contributed module.

# CONFIGURATION
Where can I enter the Zone ID and Authorization?
Go to your-site/admin/config/cloudflare-purge-form

# Note
I added an extra feature for developers/site admin who like to add Cloudflare's
credentials in settings.php and can't be changed in admin form.
The two fields will be disabled.

Copy this code in your settings.php and add your (Zone ID and Authorization)
$settings['cloudflare_purge_credentials'] = [
'zone_id' => 'insert-cf-zone-id',
'authorization' => 'insert-cf-authorization'
];

# PERMISSIONS
Drupal admin can give permissions to any Drupal role to purge cached resources
from Cloudflare.

# Legal
This module has not been built, maintained or supported by CloudFlare Inc.
This is an open source project with no association with CloudFlare Inc.
The module uses their API, that's all.


* Read more about Cloudflare's API:
   https://api.cloudflare.com/#getting-started-requests
   https://api.cloudflare.com/#getting-started-resource-ids
   https://api.cloudflare.com/#zone-purge-all-files
   https://support.cloudflare.com/hc/en-us/articles/201883834-Using-CloudFlare-and-Drupal-Five-Easy-Recommended-Steps

* Project page: https://www.drupal.org/project/cloudflare_purge

* To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/cloudflare_purge
