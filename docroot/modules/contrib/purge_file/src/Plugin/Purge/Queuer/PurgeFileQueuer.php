<?php

namespace Drupal\purge_file\Plugin\Purge\Queuer;

use Drupal\purge\Plugin\Purge\Queuer\QueuerBase;
use Drupal\purge\Plugin\Purge\Queuer\QueuerInterface;

/**
 * Queuer for the purge_file module.
 *
 * @PurgeQueuer(
 *   id = "purge_file_queuer",
 *   label = @Translation("Purge File URLs(s)"),
 *   description = @Translation("The queuer used by the Purge File module to queue file URLs to be invalidated."),
 *   enable_by_default = true,
 *   configform = "",
 * )
 */
class PurgeFileQueuer extends QueuerBase implements QueuerInterface {

}
