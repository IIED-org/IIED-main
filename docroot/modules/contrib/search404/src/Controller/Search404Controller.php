<?php

namespace Drupal\search404\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\search\Entity\SearchPage;
use Drupal\Component\Utility\Html;
use Drupal\search\Form\SearchPageForm;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Route controller for search.
 */
class Search404Controller extends ControllerBase {

  /**
   * Variable for logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Variable for messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructor for search404controller.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Inject the logger channel factory interface.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory, MessengerInterface $messenger) {
    $this->logger = $logger_factory->get('search404');
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Set title for the page not found(404) page.
   */
  public function getTitle() {
    $search_404_page_title = \Drupal::config('search404.settings')->get('search404_page_title');
    $title = !empty($search_404_page_title) ? $search_404_page_title : 'Page not found ';
    return $title;
  }

  /**
   * {@inheritdoc}
   */
  public function search404Page(Request $request) {
    $keys = $this->search404GetKeys();

    // If the current path is set as one of the ignore path,
    // then do not get into the complex search functions.
    $paths_to_ignore = \Drupal::config('search404.settings')->get('search404_ignore_paths');
    if (!empty($paths_to_ignore)) {
      $path_array = preg_split('/\R/', $paths_to_ignore);
      // If OR case enabled.
      if (\Drupal::config('search404.settings')->get('search404_use_or')) {
        $keywords = str_replace(' OR ', '/', $keys);
      }
      else {
        $keywords = str_replace(' ', '/', $keys);
      }
      $keywords = strtolower($keywords);

      $ignore_paths = [];
      foreach ($path_array as $key => $path) {
        $path = preg_replace('[ |-|_]', '/', $path);
        $path = strtolower($path);
        $ignore_paths[$key] = trim($path, '/');
      }

      // If the page matches to any of the listed paths to ignore,
      // then return default drupal 404 page title and text.
      if (in_array($keywords, $ignore_paths)) {
        $build['#title'] = 'Page not found';
        $build['#markup'] = 'The requested page could not be found.';
        return $build;
      }
    }

    if (\Drupal::moduleHandler()->moduleExists('search') && (\Drupal::currentUser()->hasPermission('search content') || \Drupal::currentUser()->hasPermission('search by page'))) {

      // Get and use the default search engine for the site.
      $search_page_repository = \Drupal::service('search.search_page_repository');
      $default_search_page = $search_page_repository->getDefaultSearchPage();

      $entity = SearchPage::load($default_search_page);
      $plugin = $entity->getPlugin();
      $build = [];
      $results = [];

      // Build the form first, because it may redirect during the submit,
      // and we don't want to build the results based on last time's request.
      $plugin->setSearch($keys, $request->query->all(), $request->attributes->all());
      if ($keys && !\Drupal::config('search404.settings')->get('search404_skip_auto_search')) {
        // If custom search enabled.
        if (\Drupal::moduleHandler()->moduleExists('search_by_page') && \Drupal::config('search404.settings')->get('search404_do_search_by_page')) {
          $this->search404CustomErrorMessage($keys);
          return $this->search404Goto('search_pages/' . $keys);
        }
        else {
          // Build search results, if keywords or other search parameters
          // are in the GET parameters. Note that we need to try the
          // search if 'keys' is in there at all, vs. being empty,
          // due to advanced search.
          if ($plugin->isSearchExecutable()) {
            // Log the search.
            if ($this->config('search.settings')->get('logging')) {
              $this->logger->notice('Searched %type for %keys.', ['%keys' => $keys, '%type' => $entity->label()]);
            }
            // Collect the search results.
            $results = $plugin->buildResults();
          }

          if (isset($results)) {
            // Jump to first result if there are results and
            // if there is only one result and if jump to first is selected or
            // if there are more than one results and force jump
            // to first is selected.
            $patterns = \Drupal::config('search404.settings')->get('search404_first_on_paths');
            $path_matches = TRUE;

            // Check if the current path exists in the set paths list.
            if (!empty($patterns)) {
              $path = str_replace(' ', '/', $keys);
              $path_matches = \Drupal::service('path.matcher')->matchPath($path, $patterns);
            }
            if (is_array($results) &&
                (
                (count($results) == 1 && \Drupal::config('search404.settings')->get('search404_jump'))
                || (count($results) >= 1 && \Drupal::config('search404.settings')->get('search404_first') && $path_matches)
                )
            ) {
              $this->search404CustomErrorMessage($keys);
              if (isset($results[0]['#result']['link'])) {
                $result_path = $results[0]['#result']['link'];
              }
              return $this->search404Goto($result_path);
            }
            else {
              $this->search404CustomErrorMessage($keys);
              // Redirecting the page for empty search404 result,
              // if redirect url is configured.
              if (!count($results) && \Drupal::config('search404.settings')->get('search404_page_redirect')) {
                $redirect_path = \Drupal::config('search404.settings')->get('search404_page_redirect');
                return $this->search404Goto($redirect_path);
              }
            }
          }
        }
      }
      else {
        $this->search404CustomErrorMessage($keys);
      }

      // Construct the search form.
      $build['search_form'] = $this->formBuilder()->getForm(SearchPageForm::class, $entity);

      // Set the custom page text on the top of the results.
      $search_404_page_text = \Drupal::config('search404.settings')->get('search404_page_text');
      if (!empty($search_404_page_text)) {
        $build['content']['#markup'] = '<div id="search404-page-text">' . $search_404_page_text . '</div>';
        $build['content']['#weight'] = -100;
      }

      // Text for, if search results is empty.
      $no_results = '';
      if (!\Drupal::config('search404.settings')->get('search404_skip_auto_search')) {
        $no_results = t('<ul>
        <li>Check if your spelling is correct.</li>
        <li>Remove quotes around phrases to search for each word individually. <em>bike shed</em> will often show more results than <em>&quot;bike shed&quot;</em>.</li>
        <li>Consider loosening your query with <em>OR</em>. <em>bike OR shed</em> will often show more results than <em>bike shed</em>.</li>
        </ul>');
      }
      $build['search_results'] = [
        '#theme' => ['item_list__search_results__' . $plugin->getPluginId(), 'item_list__search_results'],
        '#items' => $results,
        '#empty' => [
          '#markup' => '<h3>' . $this->t('Your search yielded no results.') . '</h3>' . $no_results,
        ],
        '#list_type' => 'ol',
        '#attributes' => [
          'class' => [
            'search-results',
            $plugin->getPluginId() . '-results',
          ],
        ],
        '#cache' => [
          'tags' => $entity->getCacheTags(),
        ],
      ];

      $build['pager_pager'] = [
        '#type' => 'pager',
      ];
      $build['#attached']['library'][] = 'search/drupal.search.results';
    }
    if (\Drupal::config('search404.settings')->get('search404_do_custom_search') &&
    !\Drupal::config('search404.settings')->get('search404_skip_auto_search')) {
      $custom_search_path = \Drupal::config('search404.settings')->get('search404_custom_search_path');

      // Remove query parameters before checking whether the search path
      // exists or the user has access rights.
      $custom_search_path_no_query = preg_replace('/\?.*/', '', $custom_search_path);
      $current_path = \Drupal::service('path.current')->getPath();
      $current_path = preg_replace('/[!@#$^&*();\'"+_,]/', '', $current_path);

      // All search keywords with space
      // and slash are replacing with hyphen in url redirect.
      $search_keys = '';
      // If search with OR condition enabled.
      if (\Drupal::config('search404.settings')->get('search404_use_or')) {
        $search_details = $this->search404CustomRedirection(' OR ', $current_path, $keys);
      }
      else {
        $search_details = $this->search404CustomRedirection(' ', $current_path, $keys);
      }
      $current_path = $search_details['path'];
      $search_keys = $search_details['keys'];

      // Redirect to the custom path.
      if ($current_path == "/" . $keys || $current_path == "/" . $search_keys) {
        $this->search404CustomErrorMessage($keys);
        if ($search_keys != '') {
          $custom_search_path = str_replace('@keys', $search_keys, $custom_search_path);
        }
        return $this->search404Goto("/" . $custom_search_path);
      }
    }

    if (empty($build)) {
      $build = ['#markup' => 'The page you requested does not exist.'];
    }
    return $build;
  }

  /**
   * Search404 drupal_goto helper function.
   *
   * @param string $path
   *   Parameter used to redirect.
   */
  public function search404Goto($path = '') {
    // Set redirect response.
    $response = new RedirectResponse($path);
    if (\Drupal::config('search404.settings')->get('search404_redirect_301')) {
      $response->setStatusCode(301);
    }
    return $response->send();
  }

  /**
   * Detect search from search engine.
   */
  public function search404SearchEngineQuery() {
    $engines = [
      'altavista' => 'q',
      'aol' => 'query',
      'google' => 'q',
      'bing' => 'q',
      'lycos' => 'query',
      'yahoo' => 'p',
    ];
    $parsed_url = !empty($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER']) : FALSE;
    $remote_host = !empty($parsed_url['host']) ? $parsed_url['host'] : '';
    $query_string = !empty($parsed_url['query']) ? $parsed_url['query'] : '';
    parse_str($query_string, $query);

    if (!$parsed_url === FALSE && !empty($remote_host) && !empty($query_string) && count($query)) {
      foreach ($engines as $host => $key) {
        if (strpos($remote_host, $host) !== FALSE && array_key_exists($key, $query)) {
          return trim($query[$key]);
        }
      }
    }
    return '';
  }

  /**
   * Function for searchkeys.
   *
   * Get the keys that are to be used for the search based either
   * on the keywords from the URL or from the keys from the search
   * that resulted in the 404.
   */
  public function search404GetKeys() {
    $keys = [];
    // Try to get keywords from the search result (if it was one)
    // that resulted in the 404 if the config is set.
    if (\Drupal::config('search404.settings')->get('search404_use_search_engine')) {
      $keys = $this->search404SearchEngineQuery();
    }

    // If keys are not yet populated from a search engine referer
    // use keys from the path that resulted in the 404.
    if (empty($keys)) {
      $path = \Drupal::service('path.current')->getPath();
      $path = urldecode($path);
      $path = preg_replace('/[_+-.,!@#$^&*();\'"?=]|[|]|[{}]|[<>]/', '/', $path);
      $paths = explode('/', $path);
      // Removing the custom search path value from the keyword search.
      if (\Drupal::config('search404.settings')->get('search404_do_custom_search')) {
        $custom_search_path = \Drupal::config('search404.settings')->get('search404_custom_search_path');
        $custom_search = explode('/', $custom_search_path);
        $search_path = array_diff($custom_search, ["@keys"]);
        $keywords = array_diff($paths, $search_path);
        $keys = array_filter($keywords);
      }
      else {
        $keys = array_filter($paths);
      }
      // Split the keys with - and space.
      $keys = preg_replace('/-/', ' ', $keys);
      foreach ($keys as $key => $value) {
        $keys_with_space_hypen[$key] = explode(' ', $value);
        $keys_with_space_hypen[$key] = array_filter($keys_with_space_hypen[$key]);

      }
      if (!empty($keys)) {
        $keys = call_user_func_array('array_merge', $keys_with_space_hypen);
      }
    }

    // Abort query on certain extensions, e.g: gif jpg jpeg png.
    $extensions = explode(' ', \Drupal::config('search404.settings')->get('search404_ignore_query'));
    $extensions = trim(implode('|', $extensions));
    if (!empty($extensions)) {
      foreach ($keys as $key) {
        if (preg_match("/\.($extensions)$/i", $key)) {
          return FALSE;
        }
      }
    }

    // PCRE filter from query.
    $regex_filter = \Drupal::config('search404.settings')->get('search404_regex');
    if (!empty($regex_filter)) {
      // Get filtering patterns as array.
      $filter_data = explode('[', $regex_filter);
      for ($i = 0; $i < count($filter_data); $i++) {
        if (!empty($filter_data[$i])) {
          $filter_query = explode(']', $filter_data[$i]);
          // Make the pattern for replacement.
          $regex_pattern[0] = '/' . $filter_query[0] . '/ix';
          $filter_patterns[] = trim($regex_pattern[0]);
        }
      }
      // Pattern filtering.
      $keys = preg_replace($filter_patterns, '', $keys);
      $keys = array_filter($keys);
    }

    // Ignore certain extensions from query.
    $extensions = explode(' ', \Drupal::config('search404.settings')->get('search404_ignore_extensions'));
    if (!empty($extensions)) {
      $keys = array_diff($keys, $extensions);
    }

    // Ignore certain words (use case insensitive search).
    $keys = array_udiff($keys, explode(' ', \Drupal::config('search404.settings')->get('search404_ignore')), 'strcasecmp');
    // Sanitize the keys.
    foreach ($keys as $a => $b) {
      $keys[$a] = Html::escape($b);
    }

    // When using keywords with OR operator.
    if (\Drupal::config('search404.settings')->get('search404_use_or')) {
      $keys = trim(implode(' OR ', $keys));

      // Removing the custom path string from the keywords.
      if (\Drupal::config('search404.settings')->get('search404_do_custom_search')) {
        $custom_search_path = \Drupal::config('search404.settings')->get('search404_custom_search_path');
        $custom_search = explode('/', $custom_search_path);
        $custom_path = array_diff($custom_search, ["@keys"]);
        $keys = str_replace($custom_path[0], '', $keys);
        $keys = trim(rtrim($keys, ' OR '));
      }
    }
    else {
      $keys = trim(implode(' ', $keys));
      // Removing the custom path string from the keywords.
      if (\Drupal::config('search404.settings')->get('search404_do_custom_search')) {
        $custom_search_path = \Drupal::config('search404.settings')->get('search404_custom_search_path');
        $custom_search = explode('/', $custom_search_path);
        $custom_path = array_diff($custom_search, ["@keys"]);
        $keys = trim(str_replace($custom_path[0], '', $keys));
      }
    }
    return $keys;
  }

  /**
   * Helper function to make a redirection path with custom path.
   *
   * @param string $search_type
   *   Which type of search.
   * @param string $path
   *   Searched url or keyword in the address bar.
   * @param string $keys
   *   Searching keywords.
   *
   * @return array
   *   Custom redirection path and key for comparison.
   */
  public function search404CustomRedirection($search_type, $path, $keys) {
    $search['keys'] = $keys;
    $search['path'] = $path;

    // If search keywords has space or hyphen or slash.
    if (preg_match('/-|%20/', $search['path']) || stripos($search['path'], '/') !== FALSE) {
      $search['keys'] = str_replace($search_type, '-', $search['keys']);
      if (preg_match('/%20/', $search['path'])) {
        $search['path'] = str_replace('%20', '-', $search['path']);
      }
      // If search keywords has slash.
      if (stripos($search['path'], '/') !== FALSE) {
        $search['keys'] = str_replace($search_type, '-', $search['keys']);
        $search['path'] = str_replace('/', '-', $search['path']);
        $search['path'] = substr_replace($search['path'], '/', 0, 1);
        $search['path'] = rtrim($search['path'], "-");
      }
    }
    return $search;
  }

  /**
   * Displays an error message of page not found.
   *
   * @param string $keys
   *   Keywords to display along with the error message.
   */
  public function search404CustomErrorMessage($keys) {
    $error_message = '';
    $disable_error = \Drupal::config('search404.settings')->get('search404_disable_error_message');
    if ($disable_error) {
      return;
    }
    if ($custom_error_message = \Drupal::config('search404.settings')->get('search404_custom_error_message')) {
      if (empty($keys)) {
        $error_message = str_replace('@keys', 'Invalid keys used', $custom_error_message);
      }
      else {
        $error_message = str_replace('@keys', $keys, $custom_error_message);
      }
    }
    else {
      // Invalid keys used, actually this happens
      // when no keys are populated to search with custom path.
      if (empty($keys)) {
        $error_message = $this->t('The page you requested does not exist. Invalid keywords used.');
      }
      else {
        $error_message = $this->t('The page you requested does not exist. For your convenience, a search was performed using the query %keys.', ['%keys' => Html::escape($keys)]);
      }
    }
    if (!empty($error_message)) {
      $this->messenger->addError($error_message);
    }
  }

}
