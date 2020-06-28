<?php
Namespace Drupal\migrate_pubs\Plugin\migrate\process;

use Drupal\migrate_plus\Plugin\migrate\process\EntityGenerate;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * This plugin generates entities within the process plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_generate_multiple",
 *   handle_multiples = TRUE
 * )
 *
 */
class EntityGenerateMultiple extends EntityGenerate {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {
    // Input value should be an array
    $result = [];
    if(!is_array($value)) {
    //  throw new MigrateException('Input should be an array.');
    }
    else {
      foreach ($value as $val) {
        $result[] = parent::transform($val, $migrateExecutable, $row, $destinationProperty);
      }
    }
    return $result;
  }
}
