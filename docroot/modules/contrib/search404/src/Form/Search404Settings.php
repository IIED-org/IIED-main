<?php

namespace Drupal\search404\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure settings for search404.
 */
class Search404Settings extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['system.site'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search404_settings';
  }

  /**
   * AdminToolbarToolsSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ModuleHandlerInterface $module_handler) {
    parent::__construct($configFactory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('search404.settings');
    $form['search404_jump'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Jump directly to the search result when there is only one result'),
      '#description' => $this->t('Works only with Core, Apache Solr, Lucene and Xapian searches. An HTTP status of 301 or 302 will be returned for this redirect.'),
      '#default_value' => $config->get('search404_jump'),
    ];
    $form['search404_first'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Jump directly to the first search result even when there are multiple results'),
      '#description' => $this->t('Works only with Core, Apache Solr, Lucene and Xapian searches. An HTTP status of 301 or 302 will be returned for this redirect.'),
      '#default_value' => $config->get('search404_first'),
    ];
    $form['search404_first_on_paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Jump directly to the first search result only on the listed paths.'),
      '#description' => $this->t('Enter one path per line. The "*" character is a wildcard. Example paths are blog for the blog page and blog/* for every personal blog.'),
      '#default_value' => $config->get('search404_first_on_paths'),
    ];
    $form['search404_do_google_cse'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do a Google CSE Search instead of a Drupal Search when a 404 occurs'),
      '#description' => $this->t('Requires Google CSE and Google CSE Search modules to be enabled.'),
      '#attributes' => $this->moduleHandler->moduleExists('google_cse') ? [] : ['disabled' => 'disabled'],
      '#default_value' => $config->get('search404_do_google_cse'),
    ];
    $form['search404_do_search_by_page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do a "Search by page" Search instead of a Drupal Search when a 404 occurs'),
      '#description' => $this->t('Requires "Search by page" module to be enabled.'),
      '#attributes' => $this->moduleHandler->moduleExists('search_by_page') ? [] : ['disabled' => 'disabled'],
      '#default_value' => $config->get('search404_do_search_by_page'),
    ];
    // Custom search path implementation.
    $form['search404_do_custom_search'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do a "Search" with custom path instead of a Drupal Search when a 404 occurs'),
      '#description' => $this->t('Redirect the user to a Custom search path to be entered below. Can be used to open a view with path parameter.'),
      '#default_value' => $config->get('search404_do_custom_search'),
    ];
    $form['search404_custom_search_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom search path'),
      '#description' => $this->t('The custom search path: example: myownsearch/@keys. The token "@keys" will be replaced with the search keys from the URL.'),
      '#default_value' => $config->get('search404_custom_search_path'),
      '#required' => ((!empty($form_state->getValue("search404_do_custom_search")) && $form_state->getValue("search404_do_custom_search") == TRUE) ? TRUE : FALSE),
      '#states' => [
        "visible" => [
          "input[name='search404_do_custom_search']" => ["checked" => TRUE],
        ],
      ],
    ];
    // Added for having a 301 redirect instead of the standard 302
    // (offered by the drupal_goto) than Core, Apache Solr,
    // Lucene and Xapian. Can this even be done? Meta refresh?
    $form['search404_redirect_301'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use a 301 Redirect instead of 302 Redirect'),
      '#description' => $this->t('This applies when the option to jump to first result is enabled and also for search404 results pages other than for Core, Apache Solr, Lucene and Xapian.'),
      '#default_value' => $config->get('search404_redirect_301'),
    ];
    // Added for preventing automatic search for large sites.
    $form['search404_skip_auto_search'] = [
      '#title' => $this->t('Disable auto search'),
      '#description' => $this->t('Disable automatically searching for the keywords when a page is not found and instead show the populated search form with the keywords. Useful for large sites to reduce server loads.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('search404_skip_auto_search'),
    ];
    // Disable the drupal error message when showing search results.
    $form['search404_disable_error_message'] = [
      '#title' => $this->t('Disable error message'),
      '#type' => 'checkbox',
      '#description' => $this->t('Disable the Drupal error message when search results are shown on a 404 page.'),
      '#default_value' => $config->get('search404_disable_error_message'),
    ];

    // To add custom error message.
    $form['search404_custom_error_message'] = [
      '#title' => $this->t('Custom error message'),
      '#type' => 'textfield',
      '#placeholder' => $this->t('For example, Invalid search for @keys, Sorry the page does not exist, etc.'),
      '#description' => $this->t('A custom error message instead of default Drupal message, that should be displayed when search results are shown on a 404 page, use "@keys" to insert the searched key value if necessary.'),
      '#default_value' => $config->get('search404_custom_error_message'),
    ];

    $form['advanced'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['advanced']['search404_use_or'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use OR between keywords when searching'),
      '#default_value' => $config->get('search404_use_or'),
    ];
    $form['advanced']['search404_use_customclue'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Use a custom string between keywords when searching'),
      '#description' => $this->t('using a custom string (for example a hyphen) to concatenate the keywords, has no effect if OR keyword is chosen, leave this empty to concatenate by whitespace'),
      '#default_value' => $config->get('search404_use_customclue'),
      '#states' => [
        'invisible' => [
          ':input[name="search404_use_or"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['advanced']['search404_use_search_engine'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use auto-detection of keywords from search engine referer'),
      '#description' => $this->t('This feature will conduct a search based on the query string got from a search engine if the URL of the search result points to a 404 page in the current website. Currently supported search engines: Google, Yahoo, Altavista, Lycos, Bing and AOL.'),
      '#default_value' => $config->get('search404_use_search_engine'),
    ];

    // Ignore language code from search keyword.
    $form['advanced']['search404_ignore_language'] = [
      '#title' => $this->t('Ignore language code'),
      '#type' => 'checkbox',
      '#description' => $this->t('All enabled language codes will ignored from the search query.'),
      '#default_value' => $config->get('search404_ignore_language'),
    ];

    $form['advanced']['search404_ignore'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Words to ignore'),
      '#description' => $this->t('These words will be ignored from the search query. Separate words with a space, e.g.: "and or the".'),
      '#default_value' => $config->get('search404_ignore'),
    ];
    $form['advanced']['search404_ignore_paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Specific paths to ignore'),
      '#description' => $this->t('These paths will be ignored. Site default "Page not found" page will be displayed. Enter one path per line. The "*" character is a wildcard. Example paths are blog for the blog page and blog/* for every personal blog.'),
      '#default_value' => $config->get('search404_ignore_paths', ''),
    ];
    $form['advanced']['search404_ignore_extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extensions to ignore'),
      '#description' => $this->t('These extensions will be ignored from the search query, e.g.: http://www.example.com/invalid/page.php will only search for "invalid page". Separate extensions with a space, e.g.: "htm html php". Do not include leading dot.'),
      '#default_value' => $config->get('search404_ignore_extensions'),
    ];
    $form['advanced']['search404_ignore_query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extensions to abort search'),
      '#description' => $this->t('A search will not be performed for a query ending in these extensions. Separate extensions with a space, e.g.: "gif jpg jpeg bmp png". Do not include leading dot.'),
      '#default_value' => $config->get('search404_ignore_query'),
    ];
    $form['advanced']['search404_regex'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PCRE filter'),
      '#description' => $this->t('This regular expression will be applied to filter all queries. The parts of the path that match the expression will be EXCLUDED from the search. You do NOT have to enclose the regex in forward slashes when defining the PCRE. e.g.: use "[foo]bar" instead of "/[foo]bar/". On how to use a PCRE Regex please refer <a href="http://php.net/pcre">PCRE pages in the PHP Manual</a>.'),
      '#default_value' => $config->get('search404_regex'),
    ];
    // Show custom title for the 404 search results page.
    $form['advanced']['search404_page_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom page title'),
      '#description' => $this->t('You can enter a value that will be displayed at the title of the webpage e.g. "Page not found".'),
      '#default_value' => $config->get('search404_page_title'),
    ];
    // Show custom text below the search form for the 404 search
    // results page.
    $form['advanced']['search404_page_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom page text'),
      '#default_value' => $config->get('search404_page_text'),
      '#description' => $this->t('You can enter a custom text message that can be displayed at the top of the search results, HTML formatting can be used.'),
    ];

    // Add a redirect url option for handling empty results display.
    $form['advanced']['search404_page_redirect'] = [
      '#title' => $this->t('Add a redirection url for empty search results.'),
      '#type' => 'textfield',
      '#placeholder' => 'For example, /node, /node/10, etc.',
      '#description' => $this->t('You can enter a valid url with a leading "/" to display instead of an empty result.'),
      '#default_value' => $config->get('search404_page_redirect'),
    ];

    // Helps reset the site_404 variable to search404 in case the
    // user changes it manually.
    $form['site_404'] = [
      '#type' => 'hidden',
      '#value' => 'search404',
    ];
    // Tell the user about the site_404 issue.
    $form['search404_variable_message'] = [
      '#type' => 'markup',
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#value' => $this->t('Saving this form will revert the 404 handling on the site to this module.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Validation for redirect url.
    if (!empty($form_state->getValue('search404_page_redirect'))) {
      $path = $form_state->getValue('search404_page_redirect');
      if (strpos($path, ' ') === 0) {
        $form_state->setErrorByName('search404_page_redirect', $this->t('The redirect URL should not be a space, and should not start with a space.'));
      }
      if (strpos($path, '/') !== 0) {
        $form_state->setErrorByName('search404_page_redirect', $this->t('The redirect URL should start with a slash.'));
      }
    }
    // Validation for custom search path.
    if (!empty($form_state->getValue('search404_do_custom_search'))) {
      $custom_path = $form_state->getValue('search404_custom_search_path');

      if (empty(preg_match("/@keys$/", $custom_path))) {
        $form_state->setErrorByName('search404_custom_search_path', $this->t('Custom search path should end with search key pattern "@keys".'));
      }
      $url_path = explode("@keys", $custom_path);
      if (!empty(preg_match('/[\'^Ã‚Â£!`$%&*()\{}\:.;,\[\]"@#~><>,|+Ã‚Â¬-]/', $url_path[0]))) {
        $form_state->setErrorByName('search404_custom_search_path', $this->t('Custom search path should not contain special characters other than "/?=_".'));
      }
      if (strpos($custom_path, '/') === 0) {
        $form_state->setErrorByName('search404_custom_search_path', $this->t('Custom search path should not start with a slash.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->configFactory()->getEditable('search404.settings');
    $settings->set('search404_redirect_301', $form_state->getValue('search404_redirect_301'))
      ->set('search404_do_google_cse', $form_state->getValue('search404_do_google_cse'))
      ->set('search404_do_search_by_page', $form_state->getValue('search404_do_search_by_page'))
      ->set('search404_first', $form_state->getValue('search404_first'))
      ->set('search404_first_on_paths', $form_state->getValue('search404_first_on_paths'))
      ->set('search404_jump', $form_state->getValue('search404_jump'))
      ->set('search404_use_or', $form_state->getValue('search404_use_or'))
      ->set('search404_use_customclue', $form_state->getValue('search404_use_customclue'))
      ->set('search404_ignore', $form_state->getValue('search404_ignore'))
      ->set('search404_ignore_paths', $form_state->getValue('search404_ignore_paths'))
      ->set('search404_ignore_query', $form_state->getValue('search404_ignore_query'))
      ->set('search404_ignore_extensions', $form_state->getValue('search404_ignore_extensions'))
      ->set('search404_page_text', $form_state->getValue('search404_page_text'))
      ->set('search404_page_title', $form_state->getValue('search404_page_title'))
      ->set('search404_regex', $form_state->getValue('search404_regex'))
      ->set('search404_skip_auto_search', $form_state->getValue('search404_skip_auto_search'))
      ->set('search404_use_search_engine', $form_state->getValue('search404_use_search_engine'))
      ->set('search404_disable_error_message', $form_state->getValue('search404_disable_error_message'))
      ->set('search404_custom_error_message', $form_state->getValue('search404_custom_error_message'))
      ->set('search404_page_redirect', $form_state->getValue('search404_page_redirect'))
      ->set('search404_ignore_language', $form_state->getValue('search404_ignore_language'));

    // Save custom path if the corresponding checkbox is checked.
    if (!empty($form_state->getValue('search404_do_custom_search'))) {
      $settings->set('search404_custom_search_path', $form_state->getValue('search404_custom_search_path'));
      $settings->set('search404_do_custom_search', $form_state->getValue('search404_do_custom_search'));
    }
    else {
      $settings->set('search404_custom_search_path', '');
      $settings->set('search404_do_custom_search', 0);
    }
    // Save all configurations.
    $settings->save();
    parent::submitForm($form, $form_state);
  }

}
