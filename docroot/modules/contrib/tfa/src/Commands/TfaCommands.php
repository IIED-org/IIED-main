<?php

namespace Drupal\tfa\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Database\Connection;
use Drush\Commands\DrushCommands;
use Drush\Drupal\Commands\sql\SanitizePluginInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * A Drush command file.
 */
class TfaCommands extends DrushCommands implements SanitizePluginInterface {

  /**
   * Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * TfaCommands constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   */
  public function __construct(Connection $database) {
    parent::__construct();
    $this->database = $database;
  }

  /**
   * Sanitize recovery codes and user-specific TFA data.
   *
   * @hook post-command sql-sanitize
   *
   * {@inheritdoc}
   */
  public function sanitize($result, CommandData $commandData) {
    // DBTNG does not support expressions in delete queries.
    $sql = "DELETE FROM users_data WHERE LEFT(name, 4) = 'tfa_'";
    $this->database->query($sql);
    $this->logger()->success('Removed recovery codes and other user-specific TFA data.');
  }

  /**
   * Display summary to user before confirmation.
   *
   * @hook on-event sql-sanitize-confirms
   *
   * {@inheritdoc}
   */
  public function messages(&$messages, InputInterface $input) {
    $messages[] = dt('Remove recovery codes and other user-specific TFA data.');
  }

}
