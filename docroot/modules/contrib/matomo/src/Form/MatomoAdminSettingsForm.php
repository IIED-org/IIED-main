<?php

declare(strict_types=1);

namespace Drupal\matomo\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\matomo\MatomoInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Configure Matomo settings for this site.
 */
class MatomoAdminSettingsForm extends ConfigFormBase {

  /**
   * No customization allowed to the users.
   */
  public const VISIBILITY_USER_ACCOUNT_MODE_NO_PERSONALIZATION = 0;

  /**
   * Customization allowed, tracking enabled by default.
   */
  public const VISIBILITY_USER_ACCOUNT_MODE_OPT_OUT = 1;

  /**
   * Customization allowed, tracking disabled by default.
   */
  public const VISIBILITY_USER_ACCOUNT_MODE_OPT_IN = 2;

  /**
   * When visibility on pages is conditioned by PHP code.
   */
  public const VISIBILITY_REQUEST_PATH_MODE_PHP = 2;

  /**
   * If cookie domain has more than this number of parts, adapt form example.
   */
  public const MULTI_DOMAIN_TRIGGER = 2;

  /**
   * Number of supported custom variables.
   *
   * @todo see if in the latest version of Matomo it is still the case.
   */
  public const MAX_CUSTOM_VARIABLES = 5;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The session configuration.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface
   */
  protected $sessionConfig;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Using constructor less class instantiation inspired by the Webform
    // module.
    // @see https://www.drupal.org/node/3076421
    $instance = parent::create($container);
    $instance->currentUser = $container->get('current_user');
    $instance->moduleHandler = $container->get('module_handler');
    $instance->httpClient = $container->get('http_client');
    $instance->sessionConfig = $container->get('session_configuration');
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'matomo_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['matomo.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('matomo.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];

    $form['general']['matomo_site_id'] = [
      '#default_value' => $config->get('site_id'),
      '#description' => $this->t('The user account number is unique to the websites domain. Click the <strong>Settings</strong> link in your Matomo account, then the <strong>Websites</strong> tab and enter the appropriate site <strong>ID</strong> into this field.'),
      '#required' => TRUE,
      '#size' => (int) 15,
      '#title' => $this->t('Matomo site ID'),
      '#type' => 'textfield',
    ];
    $form['general']['matomo_url_http'] = [
      '#default_value' => $config->get('url_http'),
      '#description' => $this->t('The URL to your Matomo base directory. Example: "http://www.example.com/matomo/".'),
      '#maxlength' => (int) 255,
      '#required' => TRUE,
      '#size' => (int) 80,
      '#title' => $this->t('Matomo HTTP URL'),
      '#type' => 'textfield',
    ];
    $form['general']['matomo_url_https'] = [
      '#default_value' => $config->get('url_https'),
      '#description' => $this->t('The URL to your Matomo base directory with SSL certificate installed. Required if you track a SSL enabled website. Example: "https://www.example.com/matomo/".'),
      '#maxlength' => (int) 255,
      '#size' => (int) 80,
      '#title' => $this->t('Matomo HTTPS URL'),
      '#type' => 'textfield',
    ];

    // Visibility settings.
    $form['tracking_scope'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Tracking scope'),
      '#attached' => [
        'library' => [
          'matomo/matomo.admin',
        ],
      ],
    ];

    $form['tracking']['domain_tracking'] = [
      '#type' => 'details',
      '#title' => $this->t('Domains'),
      '#group' => 'tracking_scope',
    ];

    $session_options = $this->sessionConfig->getOptions($this->getRequest());
    $cookie_domain = $session_options['cookie_domain'];
    $multiple_sub_domains = [];
    foreach (['www', 'app', 'shop'] as $subdomain) {
      if (\count(\explode('.', $cookie_domain)) > self::MULTI_DOMAIN_TRIGGER && !\is_numeric(\str_replace('.', '', $cookie_domain))) {
        $multiple_sub_domains[] = $subdomain . $cookie_domain;
      }
      // IP addresses or localhost.
      else {
        $multiple_sub_domains[] = $subdomain . '.example.com';
      }
    }

    $form['tracking']['domain_tracking']['matomo_domain_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('What are you tracking?'),
      '#options' => [
        0 => $this->t('A single domain (default)'),
        1 => $this->t('One domain with multiple subdomains'),
      ],
      0 => [
        '#description' => $this->t('Domain: @domain', ['@domain' => $this->getRequest()->getHost()]),
      ],
      1 => [
        '#description' => $this->t('Examples: @domains', ['@domains' => \implode(', ', $multiple_sub_domains)]),
      ],
      '#default_value' => $config->get('domain_mode'),
    ];

    // Page specific visibility configurations.
    $account = $this->currentUser;
    $php_access = $account->hasPermission('use php for matomo tracking visibility');
    $visibility_request_path_pages = $config->get('visibility.request_path_pages');

    $form['tracking']['page_visibility_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Pages'),
      '#group' => 'tracking_scope',
    ];

    if ($config->get('visibility.request_path_mode') == self::VISIBILITY_REQUEST_PATH_MODE_PHP && !$php_access) {
      // No permission to change PHP snippets, but keep existing settings.
      $form['tracking']['page_visibility_settings'] = [];
      $form['tracking']['page_visibility_settings']['matomo_visibility_request_path_mode'] = [
        '#type' => 'value',
        '#value' => self::VISIBILITY_REQUEST_PATH_MODE_PHP,
      ];
      $form['tracking']['page_visibility_settings']['matomo_visibility_request_path_pages'] = [
        '#type' => 'value',
        '#value' => $visibility_request_path_pages,
      ];
    }
    else {
      $options = [
        $this->t('Every page except the listed pages'),
        $this->t('The listed pages only'),
      ];
      $description = $this->t(
        "Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.",
        [
          '%blog' => '/blog',
          '%blog-wildcard' => '/blog/*',
          '%front' => '<front>',
        ]
      );

      if ($this->moduleHandler->moduleExists('php') && $php_access) {
        $options[] = $this->t('Pages on which this PHP code returns <code>TRUE</code> (experts only)');
        $title = $this->t('Pages or PHP code');
        $description .= ' ' . $this->t('If the PHP option is chosen, enter PHP code between %php. Note that executing incorrect PHP code can break your Drupal site.', ['%php' => '<?php ?>']);
      }
      else {
        $title = $this->t('Pages');
      }
      $form['tracking']['page_visibility_settings']['matomo_visibility_request_path_mode'] = [
        '#type' => 'radios',
        '#title' => $this->t('Add tracking to specific pages'),
        '#options' => $options,
        '#default_value' => $config->get('visibility.request_path_mode'),
      ];
      $form['tracking']['page_visibility_settings']['matomo_visibility_request_path_pages'] = [
        '#type' => 'textarea',
        '#title' => $title,
        '#title_display' => 'invisible',
        '#default_value' => !empty($visibility_request_path_pages) ? $visibility_request_path_pages : '',
        '#description' => $description,
        '#rows' => (int) 10,
      ];
    }

    // Render the role overview.
    $visibility_user_role_roles = $config->get('visibility.user_role_roles');

    $form['tracking']['role_visibility_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Roles'),
      '#group' => 'tracking_scope',
    ];

    $form['tracking']['role_visibility_settings']['matomo_visibility_user_role_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Add tracking for specific roles'),
      '#options' => [
        $this->t('Add to the selected roles only'),
        $this->t('Add to every role except the selected ones'),
      ],
      '#default_value' => $config->get('visibility.user_role_mode'),
    ];
    $form['tracking']['role_visibility_settings']['matomo_visibility_user_role_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#default_value' => !empty($visibility_user_role_roles) ? $visibility_user_role_roles : [],
      '#options' => \array_map(static function ($role) {
        return $role->label();
      }, $this->entityTypeManager->getStorage('user_role')->loadMultiple()),
      '#description' => $this->t('If none of the roles are selected, all users will be tracked. If a user has any of the roles checked, that user will be tracked (or excluded, depending on the setting above).'),
    ];

    // Standard tracking configurations.
    $visibility_user_account_mode = $config->get('visibility.user_account_mode');

    $form['tracking']['user_visibility_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Users'),
      '#group' => 'tracking_scope',
    ];
    $t_permission = ['%permission' => $this->t('Opt-in or out of tracking')];
    $form['tracking']['user_visibility_settings']['matomo_visibility_user_account_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow users to customize tracking on their account page'),
      '#options' => [
        self::VISIBILITY_USER_ACCOUNT_MODE_NO_PERSONALIZATION => $this->t('No customization allowed'),
        self::VISIBILITY_USER_ACCOUNT_MODE_OPT_OUT => $this->t('Tracking on by default, users with %permission permission can opt out', $t_permission),
        self::VISIBILITY_USER_ACCOUNT_MODE_OPT_IN => $this->t('Tracking off by default, users with %permission permission can opt in', $t_permission),
      ],
      '#default_value' => !empty($visibility_user_account_mode) ? $visibility_user_account_mode : 0,
    ];
    $form['tracking']['user_visibility_settings']['matomo_trackuserid'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Track User ID'),
      '#default_value' => $config->get('track.userid'),
      '#description' => $this->t('User ID enables the analysis of groups of sessions, across devices, using a unique, persistent, and non-personally identifiable ID string representing a user. <a href=":url">Learn more about the benefits of using User ID</a>.', [':url' => 'https://matomo.org/docs/user-id/']),
    ];

    // Status Code configurations.
    $form['tracking']['status_codes'] = [
      '#type' => 'details',
      '#title' => $this->t('Status Codes'),
      '#group' => 'tracking_scope',
    ];

    $form['tracking']['status_codes']['status_codes_disabled'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Prevent tracking of pages with given HTTP Status code:'),
      '#options' => [
        '404' => $this->t('404 - Not found'),
        '403' => $this->t('403 - Access denied'),
      ],
      '#default_value' => $config->get('status_codes_disabled'),
    ];

    // Link specific configurations.
    $form['tracking']['linktracking'] = [
      '#type' => 'details',
      '#title' => $this->t('Links and downloads'),
      '#group' => 'tracking_scope',
    ];
    $form['tracking']['linktracking']['matomo_trackmailto'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Track clicks on mailto links'),
      '#default_value' => $config->get('track.mailto'),
    ];
    $form['tracking']['linktracking']['matomo_trackfiles'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Track clicks on outbound links and downloads (clicks on file links) for the following extensions'),
      '#default_value' => $config->get('track.files'),
    ];
    $form['tracking']['linktracking']['matomo_trackfiles_extensions'] = [
      '#title' => $this->t('List of download file extensions'),
      '#title_display' => 'invisible',
      '#type' => 'textfield',
      '#default_value' => $config->get('track.files_extensions'),
      '#description' => $this->t('A file extension list separated by the | character that will be tracked as download when clicked. Regular expressions are supported. For example: @extensions', ['@extensions' => MatomoInterface::MATOMO_TRACKFILES_EXTENSIONS]),
      '#maxlength' => (int) 500,
      '#states' => [
        'enabled' => [
          ':input[name="matomo_trackfiles"]' => ['checked' => TRUE],
        ],
        // Note: Form required marker is not visible as title is invisible.
        'required' => [
          ':input[name="matomo_trackfiles"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $colorbox_dependencies = '<div class="admin-requirements">';
    $colorbox_dependencies .= $this->t(
      'Requires: @module-list',
      [
        '@module-list' => ($this->moduleHandler->moduleExists('colorbox') ? $this->t(
          '@module (<span class="admin-enabled">enabled</span>)',
          ['@module' => 'Colorbox']
        ) : $this->t(
          '@module (<span class="admin-missing">disabled</span>)',
          ['@module' => 'Colorbox']
        )),
      ]
    );
    $colorbox_dependencies .= '</div>';

    $form['tracking']['linktracking']['matomo_trackcolorbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Track content in colorbox modal dialogs'),
      '#description' => $this->t('Enable to track the content shown in colorbox modal windows.') . $colorbox_dependencies,
      '#default_value' => $config->get('track.colorbox'),
      '#disabled' => ($this->moduleHandler->moduleExists('colorbox') ? FALSE : TRUE),
    ];

    // Message specific configurations.
    $form['tracking']['messagetracking'] = [
      '#type' => 'details',
      '#title' => $this->t('Messages'),
      '#group' => 'tracking_scope',
    ];
    $track_messages = $config->get('track.messages');
    $form['tracking']['messagetracking']['matomo_trackmessages'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Track messages of type'),
      '#default_value' => !empty($track_messages) ? $track_messages : [],
      '#description' => $this->t('This will track the selected message types shown to users. Tracking of form validation errors may help you identifying usability issues in your site. Every message is tracked as one individual event. Messages from excluded pages cannot be tracked.'),
      '#options' => [
        'status' => $this->t('Status message'),
        'warning' => $this->t('Warning message'),
        'error' => $this->t('Error message'),
      ],
    ];

    $form['tracking']['search'] = [
      '#type' => 'details',
      '#title' => $this->t('Search'),
      '#group' => 'tracking_scope',
    ];

    $site_search_dependencies = '<div class="admin-requirements">';
    $site_search_dependencies .= $this->t(
      'Requires: @module-list',
      [
        '@module-list' => ($this->moduleHandler->moduleExists('search') ? $this->t(
          '@module (<span class="admin-enabled">enabled</span>)',
          ['@module' => 'Search']
        ) : $this->t(
          '@module (<span class="admin-missing">disabled</span>)',
          ['@module' => 'Search']
        )),
      ]
    );
    $site_search_dependencies .= '</div>';

    $form['tracking']['search']['matomo_site_search'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Track internal search'),
      '#description' => $this->t('If checked, internal search keywords are tracked.') . $site_search_dependencies,
      '#default_value' => $config->get('track.site_search'),
      '#disabled' => ($this->moduleHandler->moduleExists('search') ? FALSE : TRUE),
    ];

    // Privacy specific configurations.
    $form['tracking']['privacy'] = [
      '#type' => 'details',
      '#title' => $this->t('Privacy'),
      '#group' => 'tracking_scope',
    ];
    $form['tracking']['privacy']['matomo_privacy_donottrack'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Universal web tracking opt-out'),
      '#description' => $this->t('If enabled and your Matomo server receives the <a href=":donottrack">Do-Not-Track</a> header from the client browser, the Matomo server will not track the user. Compliance with Do Not Track could be purely voluntary, enforced by industry self-regulation, or mandated by state or federal law. Accept your visitors privacy. If they have opt-out from tracking and advertising, you should accept their personal decision.', [':donottrack' => 'https://www.eff.org/issues/do-not-track']),
      '#default_value' => $config->get('privacy.donottrack'),
    ];
    $form['tracking']['privacy']['matomo_privacy_disablecookies'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable cookies'),
      '#description' => $this->t('Matomo uses <a href=":cookie">cookies</a> to store some information about visitors between visits. Enable to disable all Matomo tracking cookies. When cookies are disabled, some data in Matomo will be <a href=":disablecookies">less accurate</a>.', [
        ':cookie' => Url::fromUri('https://en.wikipedia.org/wiki/HTTP_cookie')->toString(),
        ':disablecookies' => Url::fromUri('https://matomo.org/faq/general/faq_156/')->toString(),
      ]),
      '#default_value' => $config->get('privacy.disablecookies'),
    ];

    // Matomo page title tree view settings.
    $form['page_title_hierarchy'] = [
      '#type' => 'details',
      '#title' => $this->t('Page titles hierarchy'),
      '#description' => $this->t('This functionality enables a dynamically expandable tree view of your site page titles in your Matomo statistics. See in Matomo statistics under <em>Actions</em> > <em>Page titles</em>.'),
      '#group' => 'tracking_scope',
    ];
    $form['page_title_hierarchy']['matomo_page_title_hierarchy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show page titles as hierarchy like breadcrumbs'),
      '#description' => $this->t('By default Matomo tracks the current page title and shows you a flat list of the most popular titles. This enables a breadcrumbs like tree view.'),
      '#default_value' => $config->get('page_title_hierarchy'),
    ];
    $form['page_title_hierarchy']['matomo_page_title_hierarchy_exclude_home'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide home page from hierarchy'),
      '#description' => $this->t('If enabled, the "Home" item will be removed from the hierarchy to flatten the structure in the Matomo statistics. Hits to the home page will still be counted, but for other pages the hierarchy will start at level Home+1.'),
      '#default_value' => $config->get('page_title_hierarchy_exclude_home'),
    ];

    // Custom variables.
    $form['matomo_custom_var'] = [
      '#description' => $this->t('You can add Matomo\'s <a href=":custom_var_documentation">Custom Variables</a> here. These will be added to every page that Matomo tracking code appears on. Custom variable names and values are limited to 200 characters in length. Keep the names and values as short as possible and expect long values to get trimmed. You may use tokens in custom variable names and values. Global and user tokens are always available; on node pages, node tokens are also available.', [':custom_var_documentation' => 'https://matomo.org/docs/custom-variables/']),
      '#title' => $this->t('Custom variables'),
      '#tree' => TRUE,
      '#type' => 'details',
    ];

    $form['matomo_custom_var']['slots'] = [
      '#type' => 'table',
      '#header' => [
        ['data' => $this->t('Slot')],
        ['data' => $this->t('Name')],
        ['data' => $this->t('Value')],
        ['data' => $this->t('Scope')],
      ],
    ];

    $matomo_custom_vars = $config->get('custom.variable');

    // Matomo supports up to 5 custom variables.
    for ($i = 1; $i <= self::MAX_CUSTOM_VARIABLES; ++$i) {
      $form['matomo_custom_var']['slots'][$i]['slot'] = [
        '#default_value' => $i,
        '#description' => $this->t('Slot number'),
        '#disabled' => TRUE,
        '#size' => 1,
        '#title' => $this->t('Custom variable slot #@slot', ['@slot' => $i]),
        '#title_display' => 'invisible',
        '#type' => 'textfield',
      ];
      $form['matomo_custom_var']['slots'][$i]['name'] = [
        '#default_value' => $matomo_custom_vars[$i]['name'] ?? '',
        '#description' => $this->t('The custom variable name.'),
        '#maxlength' => (int) 100,
        '#size' => (int) 20,
        '#title' => $this->t('Custom variable name #@slot', ['@slot' => $i]),
        '#title_display' => 'invisible',
        '#type' => 'textfield',
      ];
      $form['matomo_custom_var']['slots'][$i]['value'] = [
        '#default_value' => $matomo_custom_vars[$i]['value'] ?? '',
        '#description' => $this->t('The custom variable value.'),
        '#maxlength' => (int) 255,
        '#title' => $this->t('Custom variable value #@slot', ['@slot' => $i]),
        '#title_display' => 'invisible',
        '#type' => 'textfield',
        '#element_validate' => [[static::class, 'tokenElementValidate']],
        '#token_types' => ['node'],
      ];
      if ($this->moduleHandler->moduleExists('token')) {
        $form['matomo_custom_var']['slots'][$i]['value']['#element_validate'][] = 'token_element_validate';
      }
      $form['matomo_custom_var']['slots'][$i]['scope'] = [
        '#default_value' => $matomo_custom_vars[$i]['scope'] ?? '',
        '#description' => $this->t('The scope for the custom variable.'),
        '#title' => $this->t('Custom variable slot #@slot', ['@slot' => $i]),
        '#title_display' => 'invisible',
        '#type' => 'select',
        '#options' => [
          'visit' => $this->t('Visit'),
          'page' => $this->t('Page'),
        ],
      ];
    }

    $form['matomo_custom_var']['matomo_custom_var_description'] = [
      '#type' => 'item',
      '#description' => $this->t("You can supplement Matomo's basic IP address tracking of visitors by segmenting users based on custom variables. Make sure you will not associate (or permit any third party to associate) any data gathered from your websites (or such third party websites) with any personally identifying information from any source as part of your use (or such third parties' use) of the Matomo's service."),
    ];
    if ($this->moduleHandler->moduleExists('token')) {
      $form['matomo_custom_var']['matomo_custom_var_token_tree'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['node'],
      ];
    }

    // Advanced feature configurations.
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => FALSE,
    ];

    $form['advanced']['matomo_disable_tracking'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable tracking'),
      '#description' => $this->t('If checked, the tracking code is disabled generally. This may be useful when using Matomo Tag Manager.'),
      '#default_value' => $config->get('disable_tracking'),
    ];

    $form['advanced']['matomo_cache'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Locally cache tracking code file'),
      '#description' => $this->t('If checked, the tracking code file is retrieved from your Matomo site and cached locally. It is updated daily to ensure updates to tracking code are reflected in the local copy.'),
      '#default_value' => $config->get('cache'),
    ];

    // Allow for tracking of the originating node when viewing translation sets.
    if ($this->moduleHandler->moduleExists('content_translation')) {
      $form['advanced']['matomo_translation_set'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Track translation sets as one unit'),
        '#description' => $this->t('When a node is part of a translation set, record statistics for the originating node instead. This allows for a translation set to be treated as a single unit.'),
        '#default_value' => $config->get('translation_set'),
      ];
    }

    $user_access_add_js_snippets = !$this->currentUser()->hasPermission('add js snippets for matomo');
    $user_access_add_js_snippets_permission_warning = $user_access_add_js_snippets ? ' <em>' . $this->t('This field has been disabled because you do not have sufficient permissions to edit it.') . '</em>' : '';
    $form['advanced']['codesnippet'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom JavaScript code'),
      '#open' => TRUE,
      '#description' => $this->t('You can add custom Matomo <a href=":snippets">code snippets</a> here. These will be added to every page that Matomo appears on. <strong>Do not include the &lt;script&gt; tags</strong>, and always end your code with a semicolon (;).', [':snippets' => 'https://matomo.org/docs/javascript-tracking/']),
    ];
    $form['advanced']['codesnippet']['matomo_codesnippet_before'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Code snippet (before)'),
      '#default_value' => $config->get('codesnippet.before'),
      '#disabled' => $user_access_add_js_snippets,
      '#rows' => (int) 5,
      '#description' => $this->t('Code in this textarea will be added <strong>before</strong> _paq.push(["trackPageView"]).') . $user_access_add_js_snippets_permission_warning,
    ];
    $form['advanced']['codesnippet']['matomo_codesnippet_after'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Code snippet (after)'),
      '#default_value' => $config->get('codesnippet.after'),
      '#disabled' => $user_access_add_js_snippets,
      '#rows' => (int) 5,
      '#description' => $this->t('Code in this textarea will be added <strong>after</strong> _paq.push(["trackPageView"]). This is useful if you\'d like to track a site in two accounts.') . $user_access_add_js_snippets_permission_warning,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Custom variables validation.
    foreach ($form_state->getValue(['matomo_custom_var', 'slots']) as $custom_var) {
      $form_state->setValue([
        'matomo_custom_var',
        'slots',
        $custom_var['slot'],
        'name',
      ], \trim($custom_var['name']));
      $form_state->setValue([
        'matomo_custom_var',
        'slots',
        $custom_var['slot'],
        'value',
      ], \trim($custom_var['value']));

      // Validate empty names/values.
      if (empty($custom_var['name']) && !empty($custom_var['value'])) {
        $form_state->setErrorByName('matomo_custom_var][slots][' . $custom_var['slot'] . '][name', $this->t('The custom variable @slot-number requires a <em>Name</em> if a <em>Value</em> has been provided.', ['@slot-number' => $custom_var['slot']]));
      }
      elseif (!empty($custom_var['name']) && empty($custom_var['value'])) {
        $form_state->setErrorByName('matomo_custom_var][slots][' . $custom_var['slot'] . '][value', $this->t('The custom variable @slot-number requires a <em>Value</em> if a <em>Name</em> has been provided.', ['@slot-number' => $custom_var['slot']]));
      }
    }
    $form_state->setValue('matomo_custom_var', $form_state->getValue([
      'matomo_custom_var',
      'slots',
    ]));

    // Trim some text area values.
    $form_state->setValue('matomo_visibility_request_path_pages', \trim($form_state->getValue('matomo_visibility_request_path_pages')));
    $form_state->setValue('matomo_codesnippet_before', \trim($form_state->getValue('matomo_codesnippet_before')));
    $form_state->setValue('matomo_codesnippet_after', \trim($form_state->getValue('matomo_codesnippet_after')));
    $form_state->setValue('matomo_visibility_user_role_roles', \array_filter($form_state->getValue('matomo_visibility_user_role_roles')));
    $form_state->setValue('matomo_trackmessages', \array_filter($form_state->getValue('matomo_trackmessages')));

    $this->validateUrl('matomo_url_http', $form, $form_state);
    $this->validateUrl('matomo_url_https', $form, $form_state);

    // Verify that every path is prefixed with a slash, but don't check PHP
    // code snippets and do not check for slashes if no paths configured.
    if ($form_state->getValue('matomo_visibility_request_path_mode') != self::VISIBILITY_REQUEST_PATH_MODE_PHP && !empty($form_state->getValue('matomo_visibility_request_path_pages'))) {
      $pages = \preg_split('/(\r\n?|\n)/', $form_state->getValue('matomo_visibility_request_path_pages'));
      foreach ($pages as $page) {
        if (\strpos($page, '/') !== 0 && $page !== '<front>') {
          $form_state->setErrorByName('matomo_visibility_request_path_pages', $this->t('Path "@page" not prefixed with slash.', ['@page' => $page]));
          // Drupal forms show one error only.
          break;
        }
      }
    }

    // Clear obsolete local cache if cache has been disabled.
    if ($form_state->isValueEmpty('matomo_cache') && $form['advanced']['matomo_cache']['#default_value']) {
      \matomo_clear_js_cache();
    }

    // This is for the Newbie's who cannot read a text area description.
    if (\preg_match('/(.*)<\/?script(.*)>(.*)/i', $form_state->getValue('matomo_codesnippet_before'))) {
      $form_state->setErrorByName('matomo_codesnippet_before', $this->t('Do not include the &lt;script&gt; tags in the javascript code snippets.'));
    }
    if (\preg_match('/(.*)<\/?script(.*)>(.*)/i', $form_state->getValue('matomo_codesnippet_after'))) {
      $form_state->setErrorByName('matomo_codesnippet_after', $this->t('Do not include the &lt;script&gt; tags in the javascript code snippets.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('matomo.settings');
    $config
      ->set('site_id', $form_state->getValue('matomo_site_id'))
      ->set('url_http', $form_state->getValue('matomo_url_http'))
      ->set('url_https', $form_state->getValue('matomo_url_https'))
      ->set('codesnippet.before', $form_state->getValue('matomo_codesnippet_before'))
      ->set('codesnippet.after', $form_state->getValue('matomo_codesnippet_after'))
      ->set('custom.variable', $form_state->getValue('matomo_custom_var'))
      ->set('domain_mode', $form_state->getValue('matomo_domain_mode'))
      ->set('track.files', $form_state->getValue('matomo_trackfiles'))
      ->set('track.files_extensions', $form_state->getValue('matomo_trackfiles_extensions'))
      ->set('track.colorbox', $form_state->getValue('matomo_trackcolorbox'))
      ->set('track.userid', $form_state->getValue('matomo_trackuserid'))
      ->set('track.mailto', $form_state->getValue('matomo_trackmailto'))
      ->set('track.messages', $form_state->getValue('matomo_trackmessages'))
      ->set('track.site_search', $form_state->getValue('matomo_site_search'))
      ->set('page_title_hierarchy', $form_state->getValue('matomo_page_title_hierarchy'))
      ->set('page_title_hierarchy_exclude_home', $form_state->getValue('matomo_page_title_hierarchy_exclude_home'))
      ->set('privacy.donottrack', $form_state->getValue('matomo_privacy_donottrack'))
      ->set('privacy.disablecookies', $form_state->getValue('matomo_privacy_disablecookies'))
      ->set('disable_tracking', $form_state->getValue('matomo_disable_tracking'))
      ->set('cache', $form_state->getValue('matomo_cache'))
      ->set('visibility.request_path_mode', $form_state->getValue('matomo_visibility_request_path_mode'))
      ->set('visibility.request_path_pages', $form_state->getValue('matomo_visibility_request_path_pages'))
      ->set('visibility.user_account_mode', $form_state->getValue('matomo_visibility_user_account_mode'))
      ->set('visibility.user_role_mode', $form_state->getValue('matomo_visibility_user_role_mode'))
      ->set('visibility.user_role_roles', $form_state->getValue('matomo_visibility_user_role_roles'))
      ->set('status_codes_disabled', \array_values(\array_filter($form_state->getValue('status_codes_disabled'))))
      ->save();

    if ($form_state->hasValue('matomo_translation_set')) {
      $config->set('translation_set', $form_state->getValue('matomo_translation_set'))->save();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Validate a form element that should have tokens in it.
   *
   * For example:
   *
   * @code
   * $form['my_node_text_element'] = [
   *   '#type' => 'textfield',
   *   '#title' => $this->t('Some text to token-ize that has a node context.'),
   *   '#default_value' => 'The title of this node is [node:title].',
   *   '#element_validate' => [[get_class($this), 'tokenElementValidate']],
   * ];
   *
   * @endcode
   */
  public static function tokenElementValidate(&$element, FormStateInterface $form_state) {
    $value = $element['#value'] ?? $element['#default_value'];

    if (!\mb_strlen($value)) {
      // Empty value needs no further validation since the element should depend
      // on using the '#required' FAPI property.
      return $element;
    }

    $tokens = \Drupal::token()->scan($value);
    $invalid_tokens = static::getForbiddenTokens($tokens);
    if ($invalid_tokens) {
      $form_state->setError($element, \t(
        'The %element-title is using the following forbidden tokens with personal identifying information: @invalid-tokens.',
        [
          '%element-title' => $element['#title'],
          '@invalid-tokens' => \implode(', ', $invalid_tokens),
        ]
      ));
    }

    return $element;
  }

  /**
   * Get an array of all forbidden tokens.
   *
   * @param array $value
   *   An array of token values.
   *
   * @return array
   *   A unique array of invalid tokens.
   */
  protected static function getForbiddenTokens(array $value) {
    $invalid_tokens = [];

    foreach ($value as $tokens) {
      if (\array_filter($tokens, [static::class, 'containsForbiddenToken'])) {
        $invalid_tokens = \array_merge($invalid_tokens, \array_values($tokens));
      }
    }

    return \array_unique($invalid_tokens);
  }

  /**
   * Validate if string contains forbidden tokens not allowed by privacy rules.
   *
   * @param string $token_string
   *   A string with one or more tokens to be validated.
   *
   * @return bool
   *   TRUE if blocklisted token has been found, otherwise FALSE.
   */
  protected static function containsForbiddenToken($token_string) {
    // List of strings in tokens with personal identifying information not
    // allowed for privacy reasons. See section 8.1 of the Google Analytics
    // terms of use for more detailed information.
    //
    // This list can never ever be complete. For this reason it tries to use a
    // regex and may kill a few other valid tokens, but it's the only way to
    // protect users as much as possible from admins with illegal ideas.
    //
    // User tokens are not prefixed with colon to catch 'current-user' and
    // 'user'.
    //
    $token_blocklist = [
      ':account-name]',
      ':author]',
      ':author:edit-url]',
      ':author:url]',
      ':author:path]',
      ':current-user]',
      ':current-user:original]',
      ':display-name]',
      ':fid]',
      ':mail]',
      'user:name]',
      'author:name]',
      'owner:name]',
      ':uid]',
      ':one-time-login-url]',
      ':owner]',
      ':owner:cancel-url]',
      ':owner:edit-url]',
      ':owner:url]',
      ':owner:path]',
      'user:cancel-url]',
      'user:edit-url]',
      'user:url]',
      'user:path]',
      'user:picture]',
      // addressfield_tokens.module.
      ':first-name]',
      ':last-name]',
      ':name-line]',
      ':mc-address]',
      ':thoroughfare]',
      ':premise]',
      // realname.module.
      ':name-raw]',
      // token.module.
      ':ip-address]',
    ];

    return \preg_match('/' . \implode('|', \array_map('preg_quote', $token_blocklist)) . '/i', $token_string);
  }

  /**
   * Validate if a Matomo URL is reachable.
   *
   * @param string $urlFormKey
   *   The form element key to get the URL.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  protected function validateUrl(string $urlFormKey, array &$form, FormStateInterface $form_state): void {
    $url = $form_state->getValue($urlFormKey);
    if ($url && \substr($url, -\strlen('/')) !== '/') {
      $url .= '/';
      $form_state->setValueForElement($form['general'][$urlFormKey], $url);
    }
    $url = $url . 'matomo.php';
    try {
      $result = $this->httpClient->request('GET', $url);
      if ($result->getStatusCode() != Response::HTTP_OK) {
        $this->messenger()->addWarning($this->t('The validation of "@url" failed with error "@error" (HTTP code @code).', [
          '@url' => UrlHelper::filterBadProtocol($url),
          '@error' => $result->getReasonPhrase(),
          '@code' => $result->getStatusCode(),
        ]));
      }
    }
    catch (RequestException $exception) {
      $this->messenger()->addWarning($this->t('The validation of "@url" failed with an exception "@error" (HTTP code @code).', [
        '@url' => UrlHelper::filterBadProtocol($url),
        '@error' => $exception->getMessage(),
        '@code' => $exception->getCode(),
      ]));
    }
  }

}
