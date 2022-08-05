<?php

namespace Drupal\search_api_sorts\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Search api sorts index configuration entity.
 *
 * @ConfigEntityType(
 *   id = "search_api_sorts_field",
 *   label = @Translation("Search api sorts field"),
 *   admin_permission = "administer search_api",
 *   config_prefix = "search_api_sorts_field",
 *   entity_keys = {
 *     "id" = "id",
 *     "weight" = "weight",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "display_id",
 *     "field_identifier",
 *     "status",
 *     "default_sort",
 *     "default_order",
 *     "label",
 *     "weight",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/search/search-api/sorts/{search_api_sorts_field}",
 *   },
 *   lookup_keys = {
 *     "display_id",
 *     "status"
 *   }
 * )
 */
class SearchApiSortsField extends ConfigEntityBase {

  /**
   * The ID of the search api sorts field.
   *
   * @var string
   */
  protected $id;

  /**
   * The ID of the search display.
   *
   * @var string
   */
  protected $display_id;

  /**
   * The field identifier of the search api sorts field.
   *
   * @var string
   */
  protected $field_identifier;

  /**
   * The status of the search api sorts field.
   *
   * @var bool
   */
  protected $status;

  /**
   * The default sort of the search api sorts field.
   *
   * Returns TRUE when this field is the default sorting field for this display.
   *
   * @var bool
   */
  protected $default_sort;

  /**
   * The default order of the search api sorts field.
   *
   * @var string
   */
  protected $default_order;

  /**
   * The label of the search api sorts field.
   *
   * @var string
   */
  protected $label;

  /**
   * The weight of the search api sorts field.
   *
   * @var int
   */
  protected $weight;

  /**
   * Returns the ID of the sorts field.
   *
   * @return string
   *   The ID of the sorts field.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Returns the id of the associated display.
   *
   * @return string
   *   The id of the associated display.
   */
  public function getDisplayId() {
    return $this->display_id;
  }

  /**
   * Sets the id of the associated display.
   *
   * @param string $display_id
   *   The id of the associated display.
   */
  public function setDisplayId($display_id) {
    $this->display_id = $display_id;
  }

  /**
   * Returns the field's identifier.
   *
   * @return string
   *   The field's identifier.
   */
  public function getFieldIdentifier() {
    return $this->field_identifier;
  }

  /**
   * Returns the field's identifier.
   *
   * @param string $field_identifier
   *   The field's identifier.
   */
  public function setFieldIdentifier($field_identifier) {
    $this->field_identifier = $field_identifier;
  }

  /**
   * Returns the status of the field.
   *
   * @return bool
   *   The status of the field.
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Sets the status of the field.
   *
   * @param bool $status
   *   The status of the field.
   */
  public function setStatus($status) {
    $this->status = $status;
  }

  /**
   * Returns field is the default sorts.
   *
   * @return bool
   *   Field is the default sorts.
   */
  public function getDefaultSort() {
    return $this->default_sort;
  }

  /**
   * Sets the default sorts flag.
   *
   * @param bool $default_sort
   *   Field is the default sorts.
   */
  public function setDefaultSort($default_sort) {
    $this->default_sort = $default_sort;
  }

  /**
   * Returns the label of the sorts field.
   *
   * @return string
   *   The label of the sorts field.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Sets the label of the sorts field.
   *
   * @param string $label
   *   The label of the sorts field.
   */
  public function setLabel($label) {
    $this->label = $label;
  }

  /**
   * Returns the default order of the sorts field.
   *
   * @return string
   *   The default order of the sorts field.
   */
  public function getDefaultOrder() {
    return $this->default_order;
  }

  /**
   * Sets the default order of the sorts field.
   *
   * @param string $default_order
   *   The default order of the sorts field.
   */
  public function setDefaultOrder($default_order) {
    $this->default_order = $default_order;
  }

  /**
   * Returns the weight of the sorting field.
   *
   * @return int
   *   The weight of the sorting field.
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * Sets the weight of the sorting field.
   *
   * @param int $weight
   *   The weight of the sorting field.
   */
  public function setWeight($weight) {
    $this->weight = $weight;
  }

}
