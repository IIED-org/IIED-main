<?php

namespace Drupal\search_api_sorts;

/**
 * A value object for a sorts field.
 */
class SortsField {

  /**
   * The field of the sorting.
   *
   * @var string $field
   */
  protected $field;

  /**
   * The direction of the sorting.
   *
   * @var string $order
   */
  protected $order;

  /**
   * Constructs an instance of the value object.
   *
   * @param string $field
   *   The field to sort on.
   * @param string $order
   *   The direction to sort on.
   */
  public function __construct($field, $order = NULL) {
    $this->setFieldName($field);
    $this->setOrder($order);
  }

  /**
   * Overrides the field to sort on.
   *
   * @param string $field
   *   The field of sorting.
   */
  public function setFieldName($field) {
    $this->field = $field;
  }

  /**
   * Returns the field to sort on.
   *
   * @return string
   *   The field of sorting.
   */
  public function getFieldName() {
    return $this->field;
  }

  /**
   * Overrides the order to sort on.
   *
   * @param string $order
   *   The direction of the sorting.
   */
  public function setOrder($order) {
    $this->order = ($order == 'desc' ? 'desc' : 'asc');
  }

  /**
   * Returns the direction of sorting, asc or desc.
   *
   * @return string
   *   The direction of the sorting.
   */
  public function getOrder() {
    return $this->order;
  }

}
