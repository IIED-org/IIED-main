<?php

namespace Drupal\purge\Plugin\Purge\DiagnosticCheck;

use Drupal\Core\State\StateInterface;
use Drupal\purge\Plugin\Purge\Queue\StatsTrackerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Reports how many items are in the queue and prevents unsustainable volumes.
 *
 * @PurgeDiagnosticCheck(
 *   id = "queue_size",
 *   title = @Translation("Queue size"),
 *   description = @Translation("Reports the size of the queue."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class QueueSizeDiagnosticCheck extends DiagnosticCheckBase implements DiagnosticCheckInterface {

  /**
   * The 'purge.queue.stats' service.
   *
   * @var \Drupal\purge\Plugin\Purge\Queue\StatsTrackerInterface
   */
  protected $purgeQueueStats;

  /**
   * Drupal State service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Construct a QueueSizeDiagnosticCheck object.
   *
   * @param \Drupal\purge\Plugin\Purge\Queue\StatsTrackerInterface $purge_queue_stats
   *   The queue statistics tracker.
   * @param \Drupal\Core\State\StateInterface $state
   *   The Drupal state system.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  final public function __construct(StatsTrackerInterface $purge_queue_stats, StateInterface $state, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->purgeQueueStats = $purge_queue_stats;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('purge.queue.stats'),
      $container->get('state'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->value = $this->purgeQueueStats->numberOfItems()->getInteger();
    if ($this->value === 0) {
      $this->recommendation = $this->t("Your queue is empty!");
      return self::SEVERITY_OK;
    }
    elseif ($this->value < 30000) {
      return self::SEVERITY_OK;
    }
    elseif ($this->value < 100000) {
      $this->recommendation = $this->t(
        'Your queue holds more then 30,000 items, which is quite high. Although this may naturally occur in certain configurations there is a risk that a high volume causes your server to crash at some point. High volumes can happen when no processors are clearing your queue, or when queueing outpaces processing. Please have a closer look into nature of your queue volumes, to prevent Purge from shutting down cache invalidation when the threshold of 100,000 items is reached!'
      );
      return self::SEVERITY_WARNING;
    }
    else {
      $shutdown = $this->t('Purge has shut down cache invalidation to prevent your servers from actually crashing.');
      $dangerous = $this->state->get('purge.dangerous', FALSE);
      $this->recommendation = $this->t(
        'Your queue exceeded 100,000 items! @dangerous This can happen when no processors are clearing your queue, or when queueing outpaces processing. Please first solve the structural nature of the issue by adding processing power or reducing your queue loads. Empty the queue to unblock your system.',
        ['@dangerous' => !$dangerous ? $shutdown : '']);
      return $dangerous ? self::SEVERITY_WARNING : self::SEVERITY_ERROR;
    }
  }

}
