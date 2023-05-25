# Acquia Search module

Provides integration between your Drupal site and Acquia's hosted search
service, Acquia Search [1]. Requires Search API Solr module.

[1] https://docs.acquia.com/acquia-search/

## Notes on Acquia Search data protection and core auto-switching

Acquia Search module attempts to auto-detect your environment and
automatically connect to the best-fit Acquia Search core available. This is
done to attempt to protect your data in your production Solr instance.

Depending on the Solr cores already provisioned on your Acquia Subscription, the
module will follow these rules to connect to the proper core:

* If your site is running within Acquia Cloud, Acquia Search will connect to
  the Solr core whose name matches the current environment (dev/stage/prod) and
  current multi-site instance.
* If the module can't find an appropriate Solr core above, you then need to
  configure a proper search core using settings below.

The current state is noted on the Drupal UI's general status report at
/admin/reports/status, as well as when attempting to edit each connection.

You can override this behavior using code snippets or a Drupal variable. This,
however, poses risks to your data that you should be aware of.

## Hidden settings

- `acquia_search.settings.read_only`

  Boolean value; if FALSE then search will allow writes to the selected core.
  By default, read_only is TRUE to protect solr from local content changes.

  Example settings.php override:

  `$settings['acquia_search']['read_only'] = FALSE;`


- `acquia_search.settings.override_search_core`

  String that contains the ID of an Acquia Search core. When provided (and if
  the core is available) this will force the connection to use that core
  instead of letting the module auto-switch.
  Valid use cases for setting this override is for testing locally, or
  sharing the same Solr core amongst different Acquia sites/environments.

  Here's an example for settings.php:

  - Override Acquia Search Solr search core.
    - `$settings['acquia_search']['override_search_core'] = 'ABCD-12345.prod.mysite';`
  - Override Acquia Search Solr search core per server.
    - `$settings['acquia_search']['server_overrides][$server_id] = 'ABCD-123456.env.mysite';`


- `acquia_search.settings.extract_query_handler_option`

  String that contains the extract query handler option. Default value is
  "update/extract".
  See SearchApiSolrSearchApiSolrAcquiaConnector::getExtractQuery() for details.

  Here's an example for settings.php:
  Note, unlike credentials, this option is stored in config and can be overridden:

  Override Acquia Search Solr extract query handler option.

  `$config['acquia_search']['extract_query_handler_option'] = 'some/value';`


- `acquia_search.settings.api_host`

  Override the API host for Acquia Search

  `$config['acquia_search.settings']['api_host'] = "https://api.sr.acquia.com"`
