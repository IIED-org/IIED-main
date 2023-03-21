<?php

namespace Drupal\linkchecker\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\filter\FilterPluginCollection;
use Drupal\filter\FilterPluginManager;
use Drupal\linkchecker\LinkCheckerLinkInterface;
use Drupal\linkchecker\LinkCheckerResponseCodesInterface;
use Drupal\linkchecker\LinkCheckerService;
use Drupal\linkchecker\LinkCleanUp;
use Drupal\linkchecker\LinkExtractorBatch;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Linkchecker settings for this site.
 */
class LinkCheckerAdminSettingsForm extends ConfigFormBase {

  /**
   * The service handle various date.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Manages text processing filters.
   *
   * @var \Drupal\filter\FilterPluginManager
   */
  protected $filterPluginManager;

  /**
   * The service LinkChecker.
   *
   * @var \Drupal\linkchecker\LinkCheckerService
   */
  protected $linkCheckerService;

  /**
   * The extractor batch.
   *
   * @var \Drupal\linkchecker\LinkExtractorBatch
   */
  protected $extractorBatch;

  /**
   * The link clean up.
   *
   * @var \Drupal\linkchecker\LinkCleanUp
   */
  protected $linkCleanUp;

  /**
   * The controller class for users..
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The link checker response codes service.
   *
   * @var \Drupal\linkchecker\LinkCheckerResponseCodesInterface
   */
  protected $linkCheckerResponseCodes;

  /**
   * LinkCheckerAdminSettingsForm constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DateFormatterInterface $date_formatter, FilterPluginManager $plugin_manager_filter, LinkCheckerService $linkchecker_checker, LinkExtractorBatch $extractorBatch, LinkCleanUp $linkCleanUp, UserStorageInterface $user_storage, LinkCheckerResponseCodesInterface $linkCheckerResponseCodes) {
    parent::__construct($config_factory);
    $this->dateFormatter = $date_formatter;
    $this->extractorBatch = $extractorBatch;
    $this->linkCleanUp = $linkCleanUp;
    $this->linkCheckerService = $linkchecker_checker;
    $this->filterPluginManager = $plugin_manager_filter;
    $this->userStorage = $user_storage;
    $this->linkCheckerResponseCodes = $linkCheckerResponseCodes;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('date.formatter'),
      $container->get('plugin.manager.filter'),
      $container->get('linkchecker.checker'),
      $container->get('linkchecker.extractor_batch'),
      $container->get('linkchecker.clean_up'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('linkchecker.response_codes')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'linkchecker_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['linkchecker.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('linkchecker.settings');

    $form['status'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Extraction status'),
    ];
    $form['status']['title']['#markup'] = $this->t('Progress of link extraction.');
    $total_count = $this->extractorBatch->getTotalEntitiesToProcess();
    if (!empty($total_count)) {
      $indexed_count = $this->extractorBatch->getNumberOfProcessedEntities();
      $percent = round(100 * $indexed_count / $total_count);

      $index_progress = [
        '#theme' => 'progress_bar',
        '#percent' => $percent,
        '#message' => $this->t('@indexed out of @total items have been processed.', [
          '@indexed' => $indexed_count,
          '@total' => $total_count,
        ]),
      ];
      $form['status']['bar'] = $index_progress;
    }
    else {
      $form['status']['bar']['#markup'] = '<div class="description">' . $this->t('There are no items to be indexed.') . '</div>';
    }

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#description' => $this->t('Configure the fields under each <a href=":url">content type</a> that should be scanned for broken links.', [
        ':url' => Url::fromRoute('entity.node_type.collection')
          ->toString(),
      ]),
      '#open' => TRUE,
    ];

    $form['general']['linkchecker_check_links_types'] = [
      '#type' => 'select',
      '#title' => $this->t('What type of links should be checked?'),
      '#description' => $this->t('A full qualified link (https://example.com/foo/bar) to a page is considered external, whereas an absolute (/foo/bar) or relative link (node/123) without a domain is considered internal.'),
      '#default_value' => $config->get('check_links_types'),
      '#options' => [
        LinkCheckerLinkInterface::TYPE_ALL => $this->t('Internal and external'),
        LinkCheckerLinkInterface::TYPE_EXTERNAL => $this->t('External only (https://example.com/foo/bar)'),
        LinkCheckerLinkInterface::TYPE_INTERNAL => $this->t('Internal only (node/123)'),
      ],
    ];
    $form['general']['default_url_scheme'] = [
      '#default_value' => $config->get('default_url_scheme'),
      '#type' => 'select',
      '#title' => $this->t('Default URL scheme'),
      '#description' => $this->t('Default URL scheme for scheme relative paths'),
      '#options' => [
        'http://' => 'HTTP',
        'https://' => 'HTTPS',
      ],
    ];
    $form['general']['base_path'] = [
      '#default_value' => $config->get('base_path'),
      '#type' => 'textfield',
      '#title' => $this->t('Base path'),
      '#description' => $this->t('Should not start with URL scheme'),
    ];

    $form['tag'] = [
      '#type' => 'details',
      '#title' => $this->t('Link extraction'),
      '#open' => TRUE,
    ];
    $form['tag']['linkchecker_extract_from_a'] = [
      '#default_value' => $config->get('extract.from_a'),
      '#type' => 'checkbox',
      '#title' => $this->t('Extract links in <code>&lt;a&gt;</code> and <code>&lt;area&gt;</code> tags'),
      '#description' => $this->t('Enable this checkbox if normal hyperlinks should be extracted. The anchor element defines a hyperlink, the named target destination for a hyperlink, or both. The area element defines a hot-spot region on an image, and associates it with a hypertext link.'),
    ];
    $form['tag']['linkchecker_extract_from_audio'] = [
      '#default_value' => $config->get('extract.from_audio'),
      '#type' => 'checkbox',
      '#title' => $this->t('Extract links in <code>&lt;audio&gt;</code> tags including their <code>&lt;source&gt;</code> and <code>&lt;track&gt;</code> tags'),
      '#description' => $this->t('Enable this checkbox if links in audio tags should be extracted. The audio element is used to embed audio content.'),
    ];
    $form['tag']['linkchecker_extract_from_embed'] = [
      '#default_value' => $config->get('extract.from_embed'),
      '#type' => 'checkbox',
      '#title' => $this->t('Extract links in <code>&lt;embed&gt;</code> tags'),
      '#description' => $this->t('Enable this checkbox if links in embed tags should be extracted. This is an obsolete and non-standard element that was used for embedding plugins in past and should no longer used in modern websites.'),
    ];
    $form['tag']['linkchecker_extract_from_iframe'] = [
      '#default_value' => $config->get('extract.from_iframe'),
      '#type' => 'checkbox',
      '#title' => $this->t('Extract links in <code>&lt;iframe&gt;</code> tags'),
      '#description' => $this->t('Enable this checkbox if links in iframe tags should be extracted. The iframe element is used to embed another HTML page into a page.'),
    ];
    $form['tag']['linkchecker_extract_from_img'] = [
      '#default_value' => $config->get('extract.from_img'),
      '#type' => 'checkbox',
      '#title' => $this->t('Extract links in <code>&lt;img&gt;</code> tags'),
      '#description' => $this->t('Enable this checkbox if links in image tags should be extracted. The img element is used to add images to the content.'),
    ];
    $form['tag']['linkchecker_extract_from_object'] = [
      '#default_value' => $config->get('extract.from_object'),
      '#type' => 'checkbox',
      '#title' => $this->t('Extract links in <code>&lt;object&gt;</code> and <code>&lt;param&gt;</code> tags'),
      '#description' => $this->t('Enable this checkbox if multimedia and other links in object and their param tags should be extracted. The object tag is used for flash, java, quicktime and other applets.'),
    ];
    $form['tag']['linkchecker_extract_from_video'] = [
      '#default_value' => $config->get('extract.from_video'),
      '#type' => 'checkbox',
      '#title' => $this->t('Extract links in <code>&lt;video&gt;</code> tags including their <code>&lt;source&gt;</code> and <code>&lt;track&gt;</code> tags'),
      '#description' => $this->t('Enable this checkbox if links in video tags should be extracted. The video element is used to embed video content.'),
    ];

    // Get all filters available on the system.
    $manager = $this->filterPluginManager;
    $bag = new FilterPluginCollection($manager, []);
    $filter_info = $bag->getAll();
    $filter_options = [];
    $filter_descriptions = [];
    foreach ($filter_info as $name => $filter) {
      $filter_options[$name] = $filter->getLabel();
      $filter_descriptions[$name] = [
        '#description' => $filter->getDescription(),
      ];
    }
    $form['tag']['linkchecker_filter_blacklist'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Text formats disabled for link extraction'),
      '#default_value' => $config->get('extract.filter_blacklist'),
      '#options' => $filter_options,
      '#description' => $this->t('If a filter has been enabled for an input format it runs first and afterwards the link extraction. This helps the link checker module to find all links normally created by custom filters (e.g. Markdown filter, Bbcode). All filters used as inline references (e.g. Weblink filter <code>[link: id]</code>) to other content and filters only wasting processing time (e.g. Line break converter) should be disabled. This setting does not have any effect on how content is shown on a page. This feature optimizes the internal link extraction process for link checker and prevents false alarms about broken links in content not having the real data of a link.'),
    ];
    $form['tag']['linkchecker_filter_blacklist'] = array_merge($form['tag']['linkchecker_filter_blacklist'], $filter_descriptions);

    $form['check'] = [
      '#type' => 'details',
      '#title' => $this->t('Check settings'),
      '#open' => TRUE,
    ];
    $form['check']['linkchecker_check_library'] = [
      '#type' => 'select',
      '#title' => $this->t('Check library'),
      '#description' => $this->t('Defines the library that is used for checking links.'),
      '#default_value' => $config->get('check.library'),
      '#options' => [
        'core' => $this->t('Drupal core (GuzzleClient)'),
      ],
    ];
    $form['check']['linkchecker_check_connections_max'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of simultaneous connections'),
      '#description' => $this->t('Defines the maximum number of simultaneous connections that can be opened by the server. Make sure that a single domain is not overloaded beyond RFC limits. For small hosting plans with very limited CPU and RAM it may be required to reduce the default limit.'),
      '#default_value' => $config->get('check.connections_max'),
      '#options' => array_combine([2, 4, 8, 16, 24, 32, 48, 64, 96, 128], [
        2,
        4,
        8,
        16,
        24,
        32,
        48,
        64,
        96,
        128,
      ]),
    ];
    $form['check']['linkchecker_check_useragent'] = [
      '#type' => 'select',
      '#title' => $this->t('User-Agent'),
      '#description' => $this->t('Defines the user agent that will be used for checking links on remote sites. If someone blocks the standard Drupal user agent you can try with a more common browser.'),
      '#default_value' => $config->get('check.useragent'),
      '#options' => [
        'Drupal (+https://drupal.org/)' => 'Drupal (+https://drupal.org/)',
        'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; Touch; rv:11.0) like Gecko' => 'Windows 8.1 (x64), Internet Explorer 11.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2486.0 Safari/537.36 Edge/13.10586' => 'Windows 10 (x64), Edge',
        'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0' => 'Windows 8.1 (x64), Mozilla Firefox 47.0',
        'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0' => 'Windows 10 (x64), Mozilla Firefox 47.0',
      ],
    ];
    $intervals = [
      86400,
      172800,
      259200,
      604800,
      1209600,
      2419200,
      4838400,
      7776000,
    ];
    $period = array_map([
      $this->dateFormatter,
      'formatInterval',
    ], array_combine($intervals, $intervals));
    $form['check']['linkchecker_check_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Check interval for links'),
      '#description' => $this->t('This interval setting defines how often cron will re-check the status of links.'),
      '#default_value' => $config->get('check.interval'),
      '#options' => $period,
    ];
    $form['check']['linkchecker_disable_link_check_for_urls'] = [
      '#default_value' => $config->get('check.disable_link_check_for_urls'),
      '#type' => 'textarea',
      '#title' => $this->t('Do not check the link status of links containing these URLs'),
      '#description' => $this->t('By default this list contains the domain names reserved for use in documentation and not available for registration. See <a href=":rfc-2606">RFC 2606</a>, Section 3 for more information. URLs on this list are still extracted, but the link setting <em>Check link status</em> becomes automatically disabled to prevent false alarms. If you change this list you need to clear all link data and re-analyze your content. Otherwise this setting will only affect new links added after the configuration change.', [':rfc-2606' => 'https://www.rfc-editor.org/rfc/rfc2606.txt']),
    ];
    $form['check']['linkchecker_logging_level'] = [
      '#default_value' => $config->get('logging.level'),
      '#type' => 'select',
      '#title' => $this->t('Log level'),
      '#description' => $this->t('Controls the severity of logging.'),
      '#options' => [
        RfcLogLevel::DEBUG => $this->t('Debug messages'),
        RfcLogLevel::INFO => $this->t('All messages (default)'),
        RfcLogLevel::NOTICE => $this->t('Notices and errors'),
        RfcLogLevel::WARNING => $this->t('Warnings and errors'),
        RfcLogLevel::ERROR => $this->t('Errors only'),
      ],
    ];

    $form['error'] = [
      '#type' => 'details',
      '#title' => $this->t('Error handling'),
      '#description' => $this->t('Defines error handling and custom actions to be executed if specific HTTP requests are failing.'),
      '#open' => TRUE,
    ];
    $linkchecker_default_impersonate_account = $this->userStorage->load(1);
    $form['error']['linkchecker_impersonate_account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Impersonate user account'),
      '#description' => $this->t('If below error handling actions are executed they can be impersonated with a custom user account. By default this is user %name, but you are able to assign a custom user to allow easier identification of these automatic revision updates. Make sure you select a user with <em>full</em> permissions on your site or the user may not able to access and save all content.', ['%name' => $linkchecker_default_impersonate_account->getAccountName()]),
      '#size' => 30,
      '#maxlength' => 60,
      '#autocomplete_path' => 'user/autocomplete',
      '#default_value' => $config->get('error.impersonate_account'),
    ];
    $form['error']['linkchecker_action_status_code_301'] = [
      '#title' => $this->t('Update permanently moved links'),
      '#type' => 'select',
      '#default_value' => $config->get('error.action_status_code_301'),
      '#options' => [
        0 => $this->t('Disabled'),
        1 => $this->t('After one failed check'),
        2 => $this->t('After two failed checks'),
        3 => $this->t('After three failed checks'),
        5 => $this->t('After five failed checks'),
        10 => $this->t('After ten failed checks'),
      ],
    ];
    if (\Drupal::moduleHandler()->moduleExists('dblog') && \Drupal::currentUser()->hasPermission('access site reports')) {
      $form['error']['#description'] = $this->t('If enabled, outdated links in content providing a status <em>Moved Permanently</em> (status code 301) are automatically updated to the most recent URL. If used, it is recommended to use a value of <em>three</em> to make sure this is not only a temporarily change. This feature trust sites to provide a valid permanent redirect. A new content revision is automatically created on link updates if <em>create new revision</em> is enabled in the <a href=":content_types">content types</a> publishing options. It is recommended to create new revisions for all link checker enabled content types. Link updates are nevertheless always logged in <a href=":dblog">recent log entries</a>.', [':dblog' => Url::fromRoute('entity.node_type.collection')->toString(), ':content_types' => Url::fromRoute('entity.node_type.collection')->toString()]);
    }
    else {
      $form['error']['#description'] = $this->t('If enabled, outdated links in content providing a status <em>Moved Permanently</em> (status code 301) are automatically updated to the most recent URL. If used, it is recommended to use a value of <em>three</em> to make sure this is not only a temporarily change. This feature trust sites to provide a valid permanent redirect. A new content revision is automatically created on link updates if <em>create new revision</em> is enabled in the <a href=":content_types">content types</a> publishing options. It is recommended to create new revisions for all link checker enabled content types. Link updates are nevertheless always logged.', [':content_types' => Url::fromRoute('entity.node_type.collection')->toString()]);
    }
    $form['error']['linkchecker_action_status_code_404'] = [
      '#title' => $this->t('Unpublish content on file not found error'),
      '#description' => $this->t('If enabled, content with one or more broken links (status code 404) will be unpublished and moved to moderation queue for review after the number of specified checks failed. If used, it is recommended to use a value of <em>three</em> to make sure this is not only a temporarily error.'),
      '#type' => 'select',
      '#default_value' => $config->get('error.action_status_code_404'),
      '#options' => [
        0 => $this->t('Disabled'),
        1 => $this->t('After one file not found error'),
        2 => $this->t('After two file not found errors'),
        3 => $this->t('After three file not found errors'),
        5 => $this->t('After five file not found errors'),
        10 => $this->t('After ten file not found errors'),
      ],
    ];
    $form['error']['linkchecker_ignore_response_codes'] = [
      '#default_value' => $config->get('error.ignore_response_codes'),
      '#type' => 'textarea',
      '#title' => $this->t("Don't treat these response codes as errors"),
      '#description' => $this->t('One HTTP status code per line, e.g. 403.'),
    ];

    // Buttons are only required for testing and debugging reasons.
    $description = '<p>' . $this->t('These actions will either clear all link checker tables in the database and/or analyze all selected content types, blocks and fields (see settings above) for new/updated/removed links. Normally there is no need to press one of these buttons. Use this only for immediate cleanup tasks and to force a full re-build of the links to be checked in the linkchecker tables. Keep in mind that all custom link settings will be lost if you clear link data!') . '</p>';
    $description .= '<p>' . $this->t('<strong>Note</strong>: These functions ONLY collect the links, they do not evaluate the HTTP response codes, this will be done during normal cron runs.') . '</p>';

    $form['clear'] = [
      '#type' => 'details',
      '#title' => $this->t('Maintenance'),
      '#description' => $description,
      '#open' => FALSE,
    ];
    $form['clear']['linkchecker_analyze'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reanalyze content for links'),
      '#submit' => ['::submitForm', '::submitAnalyzeLinks'],
    ];
    $form['clear']['linkchecker_clear_analyze'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear link data and analyze content for links'),
      '#submit' => ['::submitForm', '::submitClearAnalyzeLinks'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $form_state->setValue('linkchecker_disable_link_check_for_urls', trim($form_state->getValue('linkchecker_disable_link_check_for_urls')));
    $form_state->setValue('linkchecker_ignore_response_codes', trim($form_state->getValue('linkchecker_ignore_response_codes')));
    $ignore_response_codes = preg_split('/(\r\n?|\n)/', $form_state->getValue('linkchecker_ignore_response_codes'));
    foreach ($ignore_response_codes as $ignore_response_code) {
      if (!$this->linkCheckerResponseCodes->isValid($ignore_response_code)) {
        $form_state->setErrorByName('linkchecker_ignore_response_codes', $this->t('Invalid response code %code found.', ['%code' => $ignore_response_code]));
      }
    }

    // Prevent the removal of RFC documentation domains. This are the official
    // and reserved documentation domains and not "example" hostnames!
    $linkchecker_disable_link_check_for_urls = array_filter(preg_split('/(\r\n?|\n)/', $form_state->getValue('linkchecker_disable_link_check_for_urls')));
    $linkchecker_reserved_documentation_domains = "example.com\nexample.net\nexample.org";
    $form_state->setValue('linkchecker_disable_link_check_for_urls', implode("\n", array_unique(array_merge(explode("\n", $linkchecker_reserved_documentation_domains), $linkchecker_disable_link_check_for_urls))));

    // Validate impersonation user name.
    $linkchecker_impersonate_account = user_load_by_name($form_state->getValue('linkchecker_impersonate_account'));
    // @TODO: Cleanup
    // if (empty($linkchecker_impersonate_account->id())) {
    if ($linkchecker_impersonate_account && empty($linkchecker_impersonate_account->id())) {
      $form_state->setErrorByName('linkchecker_impersonate_account', $this->t('User account %name cannot found.', ['%name' => $form_state->getValue('linkchecker_impersonate_account')]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('linkchecker.settings');

    // @todo: Move it to setting save hook.
    if ((int) $config->get('check.connections_max') != (int) $form_state->getValue('linkchecker_check_connections_max')) {
      $this->linkCheckerService->queueLinks(TRUE);
    }

    $config
      ->set('scan_blocks', $form_state->getValue('linkchecker_scan_blocks'))
      ->set('check_links_types', $form_state->getValue('linkchecker_check_links_types'))
      ->set('default_url_scheme', $form_state->getValue('default_url_scheme'))
      ->set('base_path', $form_state->getValue('base_path'))
      ->set('extract.from_a', $form_state->getValue('linkchecker_extract_from_a'))
      ->set('extract.from_audio', $form_state->getValue('linkchecker_extract_from_audio'))
      ->set('extract.from_embed', $form_state->getValue('linkchecker_extract_from_embed'))
      ->set('extract.from_iframe', $form_state->getValue('linkchecker_extract_from_iframe'))
      ->set('extract.from_img', $form_state->getValue('linkchecker_extract_from_img'))
      ->set('extract.from_object', $form_state->getValue('linkchecker_extract_from_object'))
      ->set('extract.from_video', $form_state->getValue('linkchecker_extract_from_video'))
      ->set('extract.filter_blacklist', $form_state->getValue('linkchecker_filter_blacklist'))
      ->set('check.connections_max', $form_state->getValue('linkchecker_check_connections_max'))
      ->set('check.disable_link_check_for_urls', $form_state->getValue('linkchecker_disable_link_check_for_urls'))
      ->set('check.library', $form_state->getValue('linkchecker_check_library'))
      ->set('check.interval', $form_state->getValue('linkchecker_check_interval'))
      ->set('check.useragent', $form_state->getValue('linkchecker_check_useragent'))
      ->set('error.action_status_code_301', $form_state->getValue('linkchecker_action_status_code_301'))
      ->set('error.action_status_code_404', $form_state->getValue('linkchecker_action_status_code_404'))
      ->set('error.ignore_response_codes', $form_state->getValue('linkchecker_ignore_response_codes'))
      ->set('error.impersonate_account', $form_state->getValue('linkchecker_impersonate_account'))
      ->set('logging.level', $form_state->getValue('linkchecker_logging_level'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Analyze all selected for scan fields.
   */
  public function submitAnalyzeLinks(array &$form, FormStateInterface $form_state) {
    $this->extractorBatch->batch();
  }

  /**
   * Clear link data and analyze all selected for scan fields.
   */
  public function submitClearAnalyzeLinks(array &$form, FormStateInterface $form_state) {
    $this->linkCleanUp->removeAllBatch();
    $this->submitAnalyzeLinks($form, $form_state);
  }

}
