<?php

namespace Drupal\purge_file\Plugin\Purge\Processor;

use Drupal\purge\Plugin\Purge\Processor\ProcessorBase;
use Drupal\purge\Plugin\Purge\Processor\ProcessorInterface;

/**
 * Purge File Immediate processor.
 *
 * @PurgeProcessor(
 *   id = "purge_file_immediate_processor",
 *   label = @Translation("Purge File Immediate Processor"),
 *   description = @Translation("Processes the queue every time cron runs, recommended for most configurations."),
 *   enable_by_default = true,
 *   configform = "",
 * )
 */
class PurgeFileImmediateProcessor extends ProcessorBase implements ProcessorInterface {

}
