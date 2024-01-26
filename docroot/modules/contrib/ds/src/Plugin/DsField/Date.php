<?php

namespace Drupal\ds\Plugin\DsField;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The base plugin to create DS post date plugins.
 */
abstract class Date extends DsFieldBase {

  /**
   * The EntityDisplayRepository service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a Display Suite field plugin.
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter, TimeInterface $time) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $field = $this->getFieldConfiguration();
    $date_format = str_replace('ds_post_date_', '', $field['formatter'] ?? '');
    $render_key = $this->getRenderKey();

    return [
      '#markup' => $this->dateFormatter->format($this->entity()->{$render_key}->value, $date_format),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formatters() {
    $date_types = $this->entityTypeManager->getStorage('date_format')
      ->loadMultiple();

    $date_formatters = [];
    foreach ($date_types as $machine_name => $entity) {
      /** @var \Drupal\Core\Datetime\DateFormatInterface $entity */
      if ($entity->isLocked()) {
        continue;
      }
      $date_formatters['ds_post_date_' . $machine_name] = $entity->label() . ' (' . $this->dateFormatter->format($this->time->getRequestTime(), $entity->id()) . ')';
    }

    return $date_formatters;
  }

  /**
   * Returns the entity render key for this field.
   */
  public function getRenderKey() {
    return '';
  }

}
