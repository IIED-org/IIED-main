<?php

namespace Drupal\search404\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\search\Form\SearchPageForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

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
   * The path matcher service.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */

  protected $requestStack;

  /**
   * The search page repository.
   *
   * @var \Drupal\search\SearchPageRepositoryInterface
   */
  protected $searchPageRepository;

  /**
   * The file url generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructor for search404controller.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Inject the logger channel factory interface.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher service.
   * @param \Drupal\Core\Path\CurrentPathStack $currentPath
   *   The current path service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file url generator.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory, MessengerInterface $messenger, PathMatcherInterface $path_matcher, CurrentPathStack $currentPath, RequestStack $requestStack, FileUrlGeneratorInterface $fileUrlGenerator) {
    $this->logger = $logger_factory->get('search404');
    $this->messenger = $messenger;
    $this->pathMatcher = $path_matcher;
    $this->currentPath = $currentPath;
    $this->requestStack = $requestStack;
    $this->fileUrlGenerator = $fileUrlGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static(
      $container->get('logger.factory'),
      $container->get('messenger'),
      $container->get('path.matcher'),
      $container->get('path.current'),
      $container->get('request_stack'),
      $container->get('file_url_generator')
    );
    if ($container->get('module_handler')->moduleExists('search')) {
      $instance->searchPageRepository = $container->get('search.search_page_repository');
    }
    return $instance;
  }

  /**
   * {@inheritdoc}
   *
   * Set title for the page not found(404) page.
   */
  public function getTitle() {
    $search_404_page_title = $this->config('search404.settings')->get('search404_page_title');
    $title = !empty($search_404_page_title) ? Html::escape($search_404_page_title) : $this->t('Page not found');
    return $title;
  }

  /**
   * {@inheritdoc}
   */
  public function search404Page(Request $request) {
    $keys = $this->search404GetKeys();

    // Return early, if the search404GetKeys() function returns FALSE, meaning
    // that the search query contained a denied file extension:
    if ($keys === FALSE) {
      return new Response($this->get404ErrorMessage($keys), 404);
    }

    if ($keys instanceof RedirectResponse) {
      return $keys;
    }

    // If the current path is set as one of the ignore path,
    // then do not get into the complex search functions.
    $paths_to_ignore = $this->config('search404.settings')->get('search404_ignore_paths');
    if (!empty($paths_to_ignore)) {
      $path_array = preg_split('/\R/', $paths_to_ignore);
      // If OR case enabled.
      if ($this->config('search404.settings')->get('search404_use_or')) {
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
      $requested_path = $request->getPathInfo();
      $is_matched = $is_wildcard = FALSE;

      foreach ($path_array as $key => $ignored_path) {

        // Find the correct path ?
        $pattern = '/^\/?([a-z0-9\-\/]*[a-z0-9\-])+(\/\*)?/i';
        $find_ignored_path = preg_match($pattern, $ignored_path, $matches);
        if ($find_ignored_path === 1) {
          $cleaned_ignored_path = '/' . $matches[1];

          // Is it the same path.
          if ($cleaned_ignored_path === $requested_path) {
            $is_matched = TRUE;
          }

          // Is it a pattern whose match the requested path.
          if (
            array_key_exists(2, $matches)
            &&
            substr($matches[2], -1) === '*'
            &&
            strpos($requested_path, $cleaned_ignored_path) === 0
          ) {
            $is_wildcard = TRUE;
          }
        }
      }
      // Ignore this requested page ?
      if ($is_matched || $is_wildcard) {
        return new Response($this->getTitle(), 404);
      }
    }

    if ($this->moduleHandler()->moduleExists('search') && ($this->currentUser()->hasPermission('search content') || $this->currentUser()->hasPermission('search by page'))) {

      // Get and use the default search engine for the site.
      $default_search_page = $this->searchPageRepository->getDefaultSearchPage();

      $entity = $this->entityTypeManager()->getStorage('search_page')->load($default_search_page);
      $plugin = $entity->getPlugin();
      $build = [];
      $results = [];

      // Build the form first, because it may redirect during the submit,
      // and we don't want to build the results based on last time's request.
      $plugin->setSearch($keys, $request->query->all(), $request->attributes->all());
      if ($keys && !$this->config('search404.settings')->get('search404_skip_auto_search')) {
        // If custom search enabled.
        if ($this->moduleHandler()->moduleExists('search_by_page') && $this->config('search404.settings')->get('search404_do_search_by_page')) {
          $errorMessage = $this->get404ErrorMessage($keys);
          if (!empty($errorMessage)) {
            $this->messenger->addError($errorMessage);
          }

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
              $this->logger->notice(
                'Searched %type for %keys.',
                ['%keys' => $keys, '%type' => $entity->label()],
              );
            }
            // Collect the search results.
            $results = $plugin->buildResults();
          }

          if (isset($results)) {
            // Jump to first result if there are results and
            // if there is only one result and if jump to first is selected or
            // if there are more than one results and force jump
            // to first is selected.
            $patterns = $this->config('search404.settings')->get('search404_first_on_paths');
            $path_matches = TRUE;

            // Check if the current path exists in the set paths list.
            if (!empty($patterns)) {
              $path = str_replace(' ', '/', $keys);
              $path_matches = $this->pathMatcher->matchPath($path, $patterns);
            }
            if (
              is_array($results) &&
              (
                (count($results) == 1 && $this->config('search404.settings')->get('search404_jump'))
                || (count($results) >= 1 && $this->config('search404.settings')->get('search404_first') && $path_matches)
              )
            ) {
              $errorMessage = $this->get404ErrorMessage($keys);
              if (!empty($errorMessage)) {
                $this->messenger->addError($errorMessage);
              }

              if (isset($results[0]['#result']['link'])) {
                $result_path = $results[0]['#result']['link'];
              }
              return $this->search404Goto($result_path);
            }
            else {
              $errorMessage = $this->get404ErrorMessage($keys);
              if (!empty($errorMessage)) {
                $this->messenger->addError($errorMessage);
              }

              // Redirecting the page for empty search404 result,
              // if redirect url is configured.
              if (!count($results) && $this->config('search404.settings')->get('search404_page_redirect')) {
                $redirect_path = $this->config('search404.settings')->get('search404_page_redirect');
                return $this->search404Goto($redirect_path);
              }
            }
          }
        }
      }
      else {
        $errorMessage = $this->get404ErrorMessage($keys);
        if (!empty($errorMessage)) {
          $this->messenger->addError($errorMessage);
        }
      }

      // Construct the search form.
      $build['search_form'] = $this->formBuilder()->getForm(SearchPageForm::class, $entity);
      $build['search_form']['#attributes']['class'][] = 'search-404';

      // Set the custom page text on the top of the results.
      $search_404_page_text = $this->config('search404.settings')->get('search404_page_text');
      if (!empty($search_404_page_text)) {
        $build['content']['#markup'] = '<div id="search404-page-text">' . $search_404_page_text . '</div>';
        $build['content']['#weight'] = -100;
      }

      // Text for, if search results is empty.
      $no_results = '';
      if (!$this->config('search404.settings')->get('search404_skip_auto_search')) {
        $no_results = $this->t('<ul>
        <li>Check if your spelling is correct.</li>
        <li>Remove quotes around phrases to search for each word individually. <em>bike shed</em> will often show more results than <em>&quot;bike shed&quot;</em>.</li>
        <li>Consider loosening your query with <em>OR</em>. <em>bike OR shed</em> will often show more results than <em>bike shed</em>.</li>
        </ul>');
      }
      $build['search_results'] = [
        '#theme' => [
          'item_list__search_results__search404__' . $plugin->getPluginId(),
          'item_list__search_results__search404',
        ],
        '#items' => $results,
        '#empty' => [
          '#markup' => '<h3>' . $this->t('Your search yielded no results.') . '</h3>' . $no_results,
        ],
        '#list_type' => 'ol',
        '#attributes' => [
          'class' => [
            'search-404',
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
    if (
      $this->config('search404.settings')->get('search404_do_custom_search') &&
      !$this->config('search404.settings')->get('search404_skip_auto_search')
    ) {
      $custom_search_path = $this->config('search404.settings')->get('search404_custom_search_path');

      $errorMessage = $this->get404ErrorMessage($keys);
      if (!empty($errorMessage)) {
        $this->messenger->addError($errorMessage);
      }

      if ($keys != '') {
        $custom_search_path = str_replace('@keys', $keys, $custom_search_path);
      }
      return $this->search404Goto("/" . $custom_search_path);
    }

    if (empty($build)) {
      $build = ['#markup' => $this->t('The page you requested does not exist.')];
    }
    return $build;
  }

  /**
   * Search404 drupal_goto helper function.
   *
   * @param string $path
   *   Parameter used to redirect.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function search404Goto($path = '') {
    // Set redirect response.
    $response = new RedirectResponse($path);
    if ($this->config('search404.settings')->get('search404_redirect_301')) {
      $response->setStatusCode(301);
    }
    // Remove unwanted destination.
    $this->requestStack->getCurrentRequest()->query->remove('destination');
    return $response;
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
    if ($this->config('search404.settings')->get('search404_use_search_engine')) {
      $keys[] = $this->search404SearchEngineQuery();
      // Filter empty entries:
      $keys = array_filter($keys);
    }
    // If keys are not yet populated from a search engine referer
    // use keys from the path that resulted in the 404.
    if (empty($keys)) {
      $path = $this->currentPath->getPath();
      if ($this->config('search404.settings')->get('search404_search_file_entities')) {
        $fileResponse = $this->findFileEntity($path);
        if ($fileResponse) {
          return $fileResponse;
        }
      }
      $path = urldecode($path);
      $path = preg_replace('/[_+-.,!@#$^&*();\'"?=]|[|]|[{}]|[<>]/', '/', $path);
      $paths = explode('/', $path);
      // Removing the custom search path value from the keyword search.
      if ($this->config('search404.settings')->get('search404_do_custom_search')) {
        $custom_search_path = $this->config('search404.settings')->get('search404_custom_search_path');
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

    // Checking Language code in keys.
    if ($this->moduleHandler()->moduleExists('language')) {
      $ignore_language = $this->config('search404.settings')->get('search404_ignore_language');
      if ($ignore_language) {

        // List of languages enabled.
        $langcodes = $this->languageManager()->getLanguages();
        $langcodesList = array_keys($langcodes);
        $first_key = reset($keys);

        // Confirming Language code from URL path.
        $current_path = $this->requestStack->getCurrentRequest()->getPathInfo();
        $path_args = explode('/', $current_path);
        $first_argument = $path_args[1];
        if ($first_argument == $first_key) {
          // If key is in language code list.
          if (in_array($first_key, $langcodesList)) {
            array_shift($keys);
          }
        }
      }
    }

    $ignoredExtensions = explode(' ', $this->config('search404.settings')->get('search404_ignore_extensions'));
    $path = urldecode($this->currentPath->getPath());
    // If the current search result contains an ignored extension, we
    // shouldn't abort the query:
    if (!in_array(end($keys), $ignoredExtensions)) {
      $abortAllFileQueries = $this->config('search404.settings')->get('search404_deny_all_file_extensions');
      if ($abortAllFileQueries) {
        // Abort query if URL path matches any of the file extensions.
        if (preg_match("/^.+\.[a-zA-Z0-9]+$/i", $path)) {
          return FALSE;
        }
      }
      else {
        $extensionsToAbortOn = explode(' ', $this->config('search404.settings')->get('search404_deny_specific_file_extensions') ?? '');
        // "$extensionsToAbortOn" can be false, so we need two separate if
        // cases:
        if (!empty($extensionsToAbortOn)) {
          $extensionsToAbortOn = trim(implode('|', $extensionsToAbortOn));
          if (!empty($extensionsToAbortOn)) {
            // Abort query if URL ends with a specified extension to abort on:
            if (preg_match('/\.(' . $extensionsToAbortOn . ')$/', $path)) {
              return FALSE;
            }
          }
        }
      }
    }

    // PCRE filter from query.
    $regex_filter = $this->config('search404.settings')->get('search404_regex');
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

    // "$ignoredExtensions" can be false, so we need two separate if
    // cases:
    if (!empty($ignoredExtensions)) {
      $ignoredExtensions = trim(implode('|', $ignoredExtensions));
      if (!empty($ignoredExtensions)) {
        // Remove last query key, if URL ends with an extension to ignore:
        if (preg_match('/\.(' . $ignoredExtensions . ')$/', $path)) {
          array_pop($keys);
        }
      }
    }

    // Ignore certain words (use case insensitive search).
    $keys = array_udiff($keys, explode(' ', $this->config('search404.settings')->get('search404_ignore')), 'strcasecmp');
    // Sanitize the keys.
    foreach ($keys as $a => $b) {
      $keys[$a] = Html::escape($b);
    }

    // When using keywords with OR operator.
    if ($this->config('search404.settings')->get('search404_use_or')) {
      $keys = trim(implode(' OR ', $keys));
    }
    // Using a custom string to concatenate keywords.
    elseif ($this->config('search404.settings')->get('search404_use_customclue')) {
      $keys = trim(implode($this->config('search404.settings')->get('search404_use_customclue'), $keys));
    }
    // By default using whitespace between keywords.
    else {
      $keys = trim(implode(' ', $keys));
    }
    return $keys;
  }

  /**
   * Helper method to create the search 404 error message.
   *
   * @param string $keys
   *   Keywords to display along with the error message.
   */
  public function get404ErrorMessage($keys) {
    $error_message = '';
    $disable_error = $this->config('search404.settings')->get('search404_disable_error_message');
    if ($disable_error) {
      return;
    }
    if ($custom_error_message = $this->config('search404.settings')->get('search404_custom_error_message')) {
      if ($keys === FALSE) {
        $error_message = str_replace('@keys', 'No search was performed. Reason: Searching for this file extension is not allowed.', $custom_error_message);
      }
      elseif (empty($keys)) {
        $error_message = str_replace('@keys', 'No search was performed. Reason: Invalid keywords used', $custom_error_message);
      }
      else {
        $error_message = str_replace('@keys', $keys, $custom_error_message);
      }
    }
    else {
      // Invalid keys used, actually this happens
      // when no keys are populated to search with custom path.
      if ($keys === FALSE) {
        $error_message = $this->t('The page you requested does not exist. No search was performed. Reason: Searching for this file extension is not allowed.');
      }
      elseif (empty($keys)) {
        $error_message = $this->t('The page you requested does not exist. No search was performed. Reason: Invalid keywords used.');
      }
      else {
        $error_message = $this->t('The page you requested does not exist. For your convenience, a search was performed using the query %keys.', ['%keys' => Html::escape($keys)]);
      }
    }
    if (!empty($error_message)) {
      return $error_message;
    }
  }

  /**
   * Helper function to verify if the requested path points to a managed file.
   *
   * @param string $requestPath
   *   The requested path.
   *
   * @return false|\Symfony\Component\HttpFoundation\RedirectResponse
   *   If the path points to a managed file, this returns the RedirectResponse
   *   to that file, FALSE otherwise.
   */
  protected function findFileEntity(string $requestPath): RedirectResponse|bool {
    $filename = basename($requestPath);
    try {
      $storage = $this->entityTypeManager()->getStorage('file');
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      // Can be ignored, we can't find any files.
      return FALSE;
    }
    /** @var \Drupal\file\Entity\File[] $files */
    $files = $storage->loadByProperties([
      'filename' => $filename,
      'status' => 1,
    ]);
    if (!$files) {
      return FALSE;
    }
    if (count($files) === 1) {
      $destination = reset($files);
    }
    else {
      // Walk through the $requestPath and find the most specific file entity.
      $destination = NULL;
      $parts = explode('/', $requestPath);
      while ($parts) {
        array_shift($parts);
        $path = '/' . implode('/', $parts);
        foreach ($files as $file) {
          if (file_exists($file->getFileUri())) {
            $url = $this->fileUrlGenerator->generateString($file->getFileUri());
            $potential_last_pos = strlen($url) - strlen($path);
            if (strrpos($url, $path) === $potential_last_pos) {
              $destination = $file;
              break 2;
            }
          }
        }
      }
    }
    return $destination !== NULL ?
      $this->search404Goto($this->fileUrlGenerator->generateString($destination->getFileUri())) :
      FALSE;
  }

}
