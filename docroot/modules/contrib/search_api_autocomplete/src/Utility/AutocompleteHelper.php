<?php

namespace Drupal\search_api_autocomplete\Utility;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api_autocomplete\SearchInterface;

/**
 * Provides helper methods for creating autocomplete suggestions.
 */
class AutocompleteHelper implements AutocompleteHelperInterface {

  /**
   * The element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Render\ElementInfoManagerInterface|null $element_info
   *   The element info manager.
   */
  public function __construct(ElementInfoManagerInterface $element_info = NULL) {
    if (!$element_info) {
      @trigger_error('Constructing \Drupal\search_api_autocomplete\Utility\AutocompleteHelper without $element_info is deprecated in search_api_autocomplete:8.x-1.6 and will stop working in search_api_autocomplete:2.0.0. See https://www.drupal.org/node/3224354', E_USER_DEPRECATED);
      $element_info = \Drupal::service('plugin.manager.element_info');
    }
    $this->elementInfo = $element_info;
  }

  /**
   * {@inheritdoc}
   */
  public function splitKeys($keys) {
    $keys = ltrim($keys);
    // If there is whitespace or a quote on the right, all words have been
    // completed.
    if (rtrim($keys, " \"") != $keys) {
      return [rtrim($keys, ' '), ''];
    }
    if (preg_match('/^(.*?)\s*"?([\S]*)$/', $keys, $m)) {
      return [$m[1], $m[2]];
    }
    return ['', $keys];
  }

  /**
   * {@inheritdoc}
   */
  public function alterElement(array &$element, SearchInterface $search, array $data = []) {
    $element['#type'] = 'search_api_autocomplete';
    $element['#search_id'] = $search->id();
    $element['#additional_data'] = $data;

    // In case another module (for instance, Better Exposed Filters) adds a
    // "#process" key for our element type, make sure it is present on this
    // element now, too.
    $info = $this->elementInfo->getInfo('search_api_autocomplete');
    if (!empty($info['#process'])) {
      $old_process = $element['#process'] ?? [];
      $element['#process'] = array_merge($old_process, $info['#process']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access(SearchInterface $search_api_autocomplete_search, AccountInterface $account) {
    $search = $search_api_autocomplete_search;
    $permission = 'use search_api_autocomplete for ' . $search->id();
    $access = AccessResult::allowedIf($search->status())
      ->andIf(AccessResult::allowedIf($search->hasValidIndex() && $search->getIndex()->status()))
      ->andIf(AccessResult::allowedIfHasPermissions($account, [$permission, 'administer search_api_autocomplete'], 'OR'))
      ->addCacheableDependency($search);
    if ($access instanceof AccessResultReasonInterface) {
      $access->setReason("The \"$permission\" permission is required and autocomplete for this search must be enabled.");
    }
    return $access;
  }

}
