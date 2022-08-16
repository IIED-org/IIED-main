<?php

namespace Drupal\search_api_sorts_widget\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the search_api_sorts_widget entity type.
 *
 * @ConfigEntityType(
 *   id = "search_api_sorts_widget",
 *   label = @Translation("Search api sorts widget"),
 *   admin_permission = "administer search_api",
 *   config_prefix = "search_api_sorts_widget",
 *   entity_keys = {
 *     "id" = "id",
 *     "weight" = "weight"
 *   },
 *   config_export = {
 *     "id",
 *     "display_id",
 *     "status",
 *     "autosubmit",
 *     "autosubmit_hide",
 *     "sorts",
 *     "weight",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/search/search-api/sorts-widget/{search_api_sorts_widget}",
 *   },
 *   lookup_keys = {
 *     "display_id",
 *     "status"
 *   }
 * )
 */
final class SearchApiSortsWidget extends ConfigEntityBase {

  /**
   * The ID of the search api sorts widget.
   *
   * @var string
   */
  protected $id;

  /**
   * The weight of the search api sorts widget.
   *
   * @var int
   */
  protected $weight;

  /**
   * The ID of the search display.
   *
   * @var string
   */
  protected $display_id;

  /**
   * The status ot the search api sorts widget.
   *
   * @var bool
   */
  protected $status;

  /**
   * The search_api_sorts_widget autosubmit option.
   *
   * @var bool
   */
  protected $autosubmit;

  /**
   * The search_api_sorts_widget autosubmit hide button option.
   *
   * @var bool
   */
  protected $autosubmit_hide;

  /**
   * The configuration for sort fields.
   *
   * @var array
   */
  protected $sorts = [];

  /**
   * Returns the id of the associated display.
   *
   * @return string
   *   The id of the associated display.
   */
  public function getDisplayId() {
    return $this->display_id;
  }

}
