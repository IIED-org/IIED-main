<?php

namespace Drupal\acquia_search;

/**
 * Defines events for the acquia_search module.
 */
final class AcquiaSearchEvents {

  /**
   * The event fired to collect Possible Cores.
   *
   * Different modules can fetch possible cores depending on environment.
   *
   * @Event
   *
   * @see \Drupal\acquia_connector\Event\AcquiaPossibleCoresEvent
   * @see \Drupal\acquia_connector\PreferredCoreService::getListOfPossibleCores
   *
   * @var string
   */
  const GET_POSSIBLE_CORES = 'acquia_search_get_possible_cores';

}
