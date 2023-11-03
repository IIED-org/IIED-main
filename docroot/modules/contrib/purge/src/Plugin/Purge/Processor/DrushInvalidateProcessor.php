<?php

namespace Drupal\purge\Plugin\Purge\Processor;

/**
 * Processor for the 'drush p:invalidate' command.
 *
 * @PurgeProcessor(
 *   id = "drush_purge_invalidate",
 *   label = @Translation("Drush p:invalidate"),
 *   description = @Translation("Processor for the 'drush p:invalidate' command."),
 *   configform = "",
 * )
 */
class DrushInvalidateProcessor extends ProcessorBase implements ProcessorInterface {

}
