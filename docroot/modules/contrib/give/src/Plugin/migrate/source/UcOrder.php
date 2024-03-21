<?php

namespace Drupal\give\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Drupal 7 Ubercart orders
 *
 * @MigrateSource(
 *   id = "d7_uc_order_give",
 *   source_module = "uc_order"
 * )
 */
class UcOrder extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('uc_orders', 'o')
      ->fields('o')
      ->condition('order_status', ['in_checkout', 'abandoned', 'canceled', ''], 'NOT IN')
      ->orderBy('order_id', 'ASC');
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['order_id']['type'] = 'integer';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'order_id' => 'Order ID',
      'created' => 'Created',
      'name' => 'User name',
      'primary_email' => 'User email',
      'order_total' => 'Amount',
      'payment_method' => 'Method'
    ];
  }
}
