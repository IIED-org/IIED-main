<?php

namespace Drupal\views_json_source\Plugin\views\query;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Utility\Token;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views_json_source\Event\PreCacheEvent;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base query handler for views_json_source.
 *
 * @ViewsQuery(
 *   id = "views_json_source_query",
 *   title = @Translation("Views JSON Source Query"),
 *   help = @Translation("Query against API(JSON).")
 * )
 */
class ViewsJsonQuery extends QueryPluginBase {

  /**
   * To store the contextual Filter info.
   *
   * @var array
   */
  protected $contextualFilter;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The event dispatcher.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * To store the url filter info.
   *
   * @var array
   */
  protected $urlParams;

  /**
   * An array of fields.
   *
   * @var array
   */
  public $fields = [];

  /**
   * A simple array of order by clauses.
   *
   * @var array
   */
  public $orderby = [];

  /**
   * Not actually used.
   *
   * Copied over from \Drupal\views\Plugin\views\query\Sql::$where since
   * QueryPluginBase depends on it.
   *
   * @var array
   */
  public $where = [];

  /**
   * A simple array of filter clauses.
   *
   * @var array
   */
  public $filter = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config, CacheBackendInterface $cache, TimeInterface $time, ContainerAwareEventDispatcher $event_dispatcher, ClientInterface $http_client, Token $token, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->config = $config;
    $this->cache = $cache;
    $this->time = $time;
    $this->eventDispatcher = $event_dispatcher;
    $this->httpClient = $http_client;
    $this->token = $token;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('cache.default'),
      $container->get('datetime.time'),
      $container->get('event_dispatcher'),
      $container->get('http_client'),
      $container->get('token'),
      $container->get('logger.channel.views_json_source')
    );
  }

  /**
   * Generate a query from all of the information supplied to the object.
   *
   * @param bool $get_count
   *   Provide a countquery if this is true, otherwise provide a normal query.
   */
  public function query($get_count = FALSE) {
    $filters = [];

    if (isset($this->filter)) {
      foreach ($this->filter as $filter) {
        $filters[] = $filter->generate();
      }
    }
    // @todo Add an option for the filters to be 'and' or 'or'.
    return $filters;
  }

  /**
   * Builds the necessary info to execute the query.
   */
  public function build(ViewExecutable $view) {
    $view->initPager();

    // Let the pager modify the query to add limits.
    $view->pager->query();

    $view->build_info['query'] = $this->query();

    $view->build_info['query_args'] = [];
  }

  /**
   * Fetch file.
   */
  public function fetchFile($uri) {
    $parsed = parse_url($uri);
    // Check for local file.
    if (empty($parsed['host'])) {
      if (!file_exists(DRUPAL_ROOT . $uri)) {
        throw new \Exception('Local file not found.');
      }
      return file_get_contents(DRUPAL_ROOT . $uri);
    }

    $cache_id = 'views_json_source_' . md5($uri);
    if ($cache = $this->cache->get($cache_id)) {
      $json_content = $cache->data;
    }
    else {
      // Add the request headers if available.
      $headers = $this->options['headers']
        ? Json::decode($this->options['headers']) ?? []
        : [];

      $result = $this->getRequestResponse($uri, $headers);
      if (isset($result->error)) {
        $args = ['%error' => $result->error, '%uri' => $uri];
        $message = $this->t('HTTP response: %error. URI: %uri', $args);
        throw new \Exception($message);
      }

      // Save to file.
      $config = $this->config->get('views_json_source.settings');
      $cache_duration = $config->get('cache_ttl');
      $json_content = (string) $result->getBody();
      $cache_ttl = $this->time->getRequestTime() + $cache_duration;

      // Dispatch event before caching json_content.
      $event = new PreCacheEvent($this->view, $json_content);
      $this->eventDispatcher->dispatch($event, PreCacheEvent::VIEWS_JSON_SOURCE_PRE_CACHE);
      $json_content = $event->getViewData();

      $this->cache->set($cache_id, $json_content, $cache_ttl);
    }

    return $json_content;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view) {
    $start = microtime(TRUE);

    // Avoid notices about $view->execute_time being undefined if the query
    // doesn't finish.
    $view->execute_time = NULL;

    // Make sure that an xml file exists.
    // This could happen if you come from the add wizard to the actual views
    // edit page.
    if (empty($this->options['json_file'])) {
      return FALSE;
    }

    $data = new \stdClass();
    try {
      // Replace any dynamic character if any.
      $url = $this->options['json_file'];

      // Replace any Drupal tokens in the url EG: [site:url].
      $url = $this->token->replace($url);

      while ($param = $this->getUrlParam()) {
        $url = preg_replace('/' . preg_quote('%', '/') . '/', $param, $url, 1);
      }
      $data->contents = $this->fetchFile($url);
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      return;
    }

    // When content is empty, parsing it is pointless.
    if (!$data->contents) {
      if ($this->options['show_errors']) {
        $this->logger->warning($this->t('Views JSON Backend: File is empty.'));
      }
      return;
    }

    // Go!
    $ret = $this->parse($view, $data);
    $view->execute_time = microtime(TRUE) - $start;

    if (!$ret && $this->options['show_errors']) {
      $tmp = [
        JSON_ERROR_NONE => $this->t('No error has occurred'),
        JSON_ERROR_DEPTH => $this->t('The maximum stack depth has been exceeded'),
        JSON_ERROR_STATE_MISMATCH => $this->t('Invalid or malformed JSON'),
        JSON_ERROR_CTRL_CHAR => $this->t('Control character error, possibly incorrectly encoded'),
        JSON_ERROR_SYNTAX => $this->t('Syntax error'),
        JSON_ERROR_UTF8 => $this->t('Malformed UTF-8 characters, possibly incorrectly encoded'),
      ];
      $msg = $tmp[json_last_error()] . ' - ' . $this->options['json_file'];
      $this->logger->error($msg);
    }
  }

  /**
   * Fetch data in array according to apath.
   *
   * @param string $apath
   *   Something like '1/name/0'.
   * @param array $array
   *   The json document content.
   *
   * @return array
   *   The json document matching the path.
   */
  public function apath($apath, array $array) {
    $r = & $array;
    $paths = explode('/', trim($apath, '//'));
    foreach ($paths as $path) {
      if ($path == '%') {
        // Replace with the contextual filter value.
        $key = $this->getCurrentContextualFilter();
        if (!empty($key) && array_key_exists($key, $r)) {
          $r = $r[$key];
        }
      }
      elseif (stripos($path, '=') !== FALSE) {
        $search_data = explode('=', $path);
        $array_key = $search_data[0];
        $array_value = $search_data[1];
        foreach ($r as $key => $row) {
          if (isset($row[$array_key]) && $row[$array_key] == $array_value) {
            $r = $r[$key];
            break;
          }
        }
      }
      elseif (is_array($r) && isset($r[$path])) {
        $r = & $r[$path];
      }
      elseif (is_object($r)) {
        $r = & $r->$path;
      }
      else {
        break;
      }
    }

    return $r;
  }

  /**
   * Define ops for using in filter.
   */
  public function ops($op, $l, $r) {
    $table = [
      '=' => function ($l, $r) {
        return $l == $r;
      },
      '!=' => function ($l, $r) {
        return $l != $r;
      },
      'contains' => function ($l, $r) {
        return $l !== NULL && $r !== NULL ? stripos($l, $r) !== FALSE : FALSE;
      },
      'starts' => function ($l, $r) {
        return $l !== NULL && $r !== NULL ? strpos($l, $r) === 0 : FALSE;
      },
      'not_starts' => function ($l, $r) {
        return $l !== NULL && $r !== NULL ? strpos($l, $r) !== 0 : FALSE;
      },
      'ends' => function ($l, $r) {
        $len = $l !== NULL && $r !== NULL ? strlen($r) : 0;
        return $len > 0 ? substr($l, -$len) === $r : TRUE;
      },
      'not_ends' => function ($l, $r) {
        $len = $l !== NULL && $r !== NULL ? strlen($r) : 0;
        return $len > 0 ? substr($l, -$len) !== $r : TRUE;
      },
      'not' => function ($l, $r) {
        return $l !== NULL && $r !== NULL ? stripos($l, $r) === FALSE : FALSE;
      },
      'shorterthan' => function ($l, $r) {
        return $l !== NULL ? strlen($l) < $r : FALSE;
      },
      'longerthan' => function ($l, $r) {
        return $l !== NULL ? strlen($l) > $r : FALSE;
      },
      'regular_expression' => function ($l, $r) {
        return $l !== NULL && $r !== NULL ? preg_match($r, $l) === 1 : FALSE;
      },
    ];

    return array_key_exists($op, $table) ? call_user_func($table[$op], $l, $r) : FALSE;
  }

  /**
   * Parse.
   */
  public function parse(ViewExecutable &$view, $data) {
    $ret = Json::decode($data->contents);
    if (!$ret) {
      return FALSE;
    }

    // Get rows.
    $ret = $this->apath($this->options['row_apath'], $ret) ?? [];

    // Get group operator.
    $filter_group = $view->display_handler->getOption('filter_groups');
    $group_conditional_operator = $filter_group['groups'][1] ?? "AND";

    // Filter.
    foreach ($ret as $k => $row) {
      $check = TRUE;
      foreach ($view->build_info['query'] as $filter) {
        // Filter only when value is present.
        if (!empty($filter[0])) {
          $filter_keys = explode('/', trim($filter[0], '//'));
          $l = NestedArray::getValue($row, $filter_keys);
          $check = $this->ops($filter[1], $l, $filter[2]);
          if ($group_conditional_operator === "AND") {
            // With AND condition.
            if (!$check) {
              break;
            }
          }
          elseif ($group_conditional_operator === "OR") {
            // With OR conditions.
            if ($check) {
              break;
            }
          }
        }
      }
      if (!$check) {
        unset($ret[$k]);
      }
    }

    // Save the total number of results in the view.
    $total_number_results = count($ret);

    try {
      if ($view->pager->useCountQuery() || !empty($view->get_total_rows)) {
        // Hackish execute_count_query implementation.
        $view->pager->total_items = count($ret);
        if (!empty($view->pager->options['offset'])) {
          $view->pager->total_items -= $view->pager->options['offset'];
        }

        $view->pager->updatePageInfo();
      }

      if (!empty($this->orderby)) {
        // Array reverse, because the most specific are first.
        foreach (array_reverse($this->orderby) as $orderby) {
          $this->sort($ret, $orderby['field'], $orderby['order']);
        }
      }

      // Deal with offset & limit.
      $offset = !empty($this->offset) ? intval($this->offset) : 0;
      $limit = !empty($this->limit) ? intval($this->limit) : 0;
      $ret = $limit ? array_slice($ret, $offset, $limit) : array_slice($ret, $offset);

      $result = [];
      if ($this->options['single_payload']) {
        $result[] = $this->parseRow(NULL, $ret, $ret);
      }
      else {
        foreach ($ret as $row) {
          $new_row = $this->parseRow(NULL, $row, $row);
          $result[] = $new_row;
        }
      }

      foreach ($result as $row) {
        $view->result[] = new ResultRow($row);
      }

      // Re-index array.
      $index = 0;
      foreach ($view->result as &$row) {
        $row->index = $index++;
      }

      // Pass the total number of the results.
      $view->total_rows = $total_number_results;

      $view->pager->postExecute($view->result);

      return TRUE;
    }
    catch (\Exception $e) {
      $view->result = [];
      if (!empty($view->live_preview)) {
        $this->logger->error($e->getMessage());
      }
      else {
        $this->logger->notice($e->getMessage());
      }
    }
  }

  /**
   * Parse row.
   *
   * A recursive function to flatten the JSON object.
   * Example:
   * {person:{name:{first_name:"John", last_name:"Doe"}}}
   * becomes:
   * $row->person/name/first_name = "John",
   * $row->person/name/last_name = "Doe"
   */
  public function parseRow($parent_key, $parent_row, &$row) {

    foreach ($parent_row as $key => $value) {
      if (is_array($value)) {
        unset($row[$key]);
        $this->parseRow(
          is_null($parent_key) ? $key : $parent_key . '/' . $key,
          $value,
          $row
        );
      }
      else {
        if ($parent_key) {
          $new_key = $parent_key . '/' . $key;
          $row[$new_key] = $value;
        }
        else {
          $row[$key] = $value;
        }
      }
    }

    return $row;
  }

  /**
   * Option definition.
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['json_file'] = ['default' => ''];
    $options['row_apath'] = ['default' => ''];
    $options['headers'] = ['default' => ''];
    $options['request_method'] = ['default' => 'get'];
    $options['request_body'] = ['default' => ''];
    $options['single_payload'] = ['default' => ''];
    $options['show_errors'] = ['default' => TRUE];

    return $options;
  }

  /**
   * Options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['json_file'] = [
      '#type' => 'textfield',
      '#title' => $this->t('JSON File'),
      '#default_value' => $this->options['json_file'],
      '#description' => $this->t('The URL or relative path to the JSON file(starting with a slash "/").<br />Note: Can use Drupal token as well.'),
      '#maxlength' => 1024,
    ];
    $form['row_apath'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Row Apath'),
      '#default_value' => $this->options['row_apath'],
      '#description' => $this->t("Apath to records.<br />Apath is just a simple array item find method. Ex:<br /><pre>['data' => \n\t['records' => \n\t\t[\n\t\t\t['firstname' => 'abc', 'lastname' => 'pqr'],\n\t\t\t['firstname' => 'xyz', 'lastname' => 'aaa']\n\t\t]\n\t]\n]</pre><br />You want 'records', so Apath could be set to 'data/records'. <br />Notice: Use '%' as wildcard to get the child contents - EG: '%/records', Also add the contextual filter to replace the wildcard('%') with 'data'."),
      '#required' => TRUE,
    ];
    $form['headers'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Headers'),
      '#default_value' => $this->options['headers'],
      '#description' => $this->t("Headers to be passed for the REST call.<br />Pass the headers as JSON string. Ex:<br /><pre>{&quot;Authorization&quot;:&quot;Basic xxxxx&quot;,&quot;Content-Type&quot;:&quot;application/json&quot;}</pre><br />.Here we are passing 2 headers for making the REST API call."),
      '#required' => FALSE,
    ];
    $form['request_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Request method'),
      '#default_value' => $this->options['request_method'],
      '#options' => [
        'get' => $this->t('GET'),
        'post' => $this->t('POST'),
      ],
      '#description' => $this->t('The request method to the REST call.'),
    ];
    $form['request_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Request body'),
      '#default_value' => $this->options['request_body'],
      '#states' => [
        'visible' => [
          'select[name="query[options][request_method]"]' => [
            'value' => 'post',
          ],
        ],
      ],
      '#description' => $this->t('The POST request body to the REST call.<br/>Pass the form values as JSON string. Ex: <br/><pre>[{&quot;name&quot;:&quot;item_key&quot;,&quot;contents&quot;:&quot;item value&quot;,&quot;headers&quot;:{&quot;Content-type&quot;:&quot;application/json&quot;}}]</pre> See <a href="https://docs.guzzlephp.org/en/stable/request-options.html#multipart" target="_blank">the documentation for GuzzleHttp multipart request options</a>.'),
    ];
    $form['single_payload'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Response contain single node.'),
      '#default_value' => $this->options['single_payload'],
      '#description' => $this->t('Select the checkbox, if the response contains a single item(not a listing API).'),
      '#required' => FALSE,
    ];
    $form['show_errors'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show JSON errors'),
      '#default_value' => $this->options['show_errors'],
      '#description' => $this->t('If there were any errors during JSON parsing, display them. It is recommended to leave this on during development.'),
      '#required' => FALSE,
    ];
  }

  /**
   * Add a field.
   */
  public function addField($table, $field, $alias = '', $params = []) {
    $alias = $field;

    // Add field info array.
    if (empty($this->fields[$field])) {
      $this->fields[$field] = [
        'field' => $field,
        'table' => $table,
        'alias' => $alias,
      ] + $params;
    }

    return $field;
  }

  /**
   * Add Order By.
   */
  public function addOrderBy($table, $field = NULL, $orderby = 'ASC') {
    $this->orderby[] = ['field' => $field, 'order' => $orderby];
  }

  /**
   * Add Filter.
   */
  public function addFilter($filter) {
    $this->filter[] = $filter;
  }

  /**
   * Sort.
   */
  public function sort(&$result, $field, $order) {
    if (strtolower($order) == 'asc') {
      usort($result, $this->sortAsc($field));
    }
    else {
      usort($result, $this->sortDesc($field));
    }
  }

  /**
   * Sort Ascending.
   */
  public function sortAsc($key) {
    return function ($a, $b) use ($key) {
      $keyArray = explode('/', $key);
      foreach ($keyArray as $indexKey) {
        $a = $a[$indexKey];
        $b = $b[$indexKey];
      }
      $a_value = $a ?? '';
      $b_value = $b ?? '';
      return strnatcasecmp($a_value, $b_value);
    };
  }

  /**
   * Sort Descending.
   */
  public function sortDesc($key) {
    return function ($a, $b) use ($key) {
      $keyArray = explode("/", $key);
      foreach ($keyArray as $indexKey) {
        $a = $a[$indexKey];
        $b = $b[$indexKey];
      }
      $a_value = $a ?? '';
      $b_value = $b ?? '';
      return -strnatcasecmp($a_value, $b_value);
    };
  }

  /**
   * To store the filter values required to pick the node from the json.
   */
  public function addContextualFilter($filter) {
    $this->contextualFilter[] = $filter;
  }

  /**
   * To get the next filter value to pick the node from the json.
   */
  public function getCurrentContextualFilter() {
    if (!isset($this->contextualFilter)) {
      return [];
    }

    $filter = current($this->contextualFilter);
    next($this->contextualFilter);
    return $filter;
  }

  /**
   * To store the filter values required to pick the node from the json.
   */
  public function addUrlParams($filter) {
    $this->urlParams[] = $filter;
  }

  /**
   * To get the next filter value to pick the node from the json.
   */
  public function getUrlParam() {
    if (!isset($this->urlParams)) {
      return [];
    }

    $filter = current($this->urlParams);
    next($this->urlParams);
    return $filter;
  }

  /**
   * Get request result.
   *
   * @param string $uri
   *   The request uri.
   * @param array $headers
   *   The request headers.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The request response.
   */
  private function getRequestResponse(string $uri, array $headers = []) {
    $this->options['request_method'] = $this->options['request_method'] ?? 'get';
    switch ($this->options['request_method']) {
      case 'post':
        $options = [];
        $options['headers'] = $headers;
        if (!empty($this->options['request_body'])) {
          $options['multipart'] = Json::decode($this->options['request_body']);
        }
        $result = $this->httpClient->post($uri, $options);
        break;

      default:
        $result = $this->httpClient->get($uri, ['headers' => $headers]);
        break;
    }

    return $result;
  }

}
