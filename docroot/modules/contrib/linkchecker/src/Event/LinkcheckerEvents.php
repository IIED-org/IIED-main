<?php

namespace Drupal\linkchecker\Event;

/**
 * Provides the event names of the Linkchecker module.
 */
final class LinkcheckerEvents {

  /**
   * Dispatched when the linkchecker builds the headers for its requests.
   */
  public const BUILD_HEADER = 'linkchecker.build_header';

}
