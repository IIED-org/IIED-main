<?php

namespace Drupal\purge\Plugin\Purge\Queuer;

/**
 * Queuer for the 'drush p:queue-add' command.
 *
 * @PurgeQueuer(
 *   id = "drush_purge_queue_add",
 *   label = @Translation("Drush p:queue-add"),
 *   description = @Translation("Queuer for the 'drush p:queue-add' command."),
 *   configform = "",
 * )
 */
class DrushQueueAddQueuer extends QueuerBase implements QueuerInterface {

}
