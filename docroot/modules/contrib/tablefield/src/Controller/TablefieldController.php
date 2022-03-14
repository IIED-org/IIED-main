<?php

namespace Drupal\tablefield\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller routines for tablefield routes.
 */
class TablefieldController {

  /**
   * Check access to the export page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check the permission for view.
   * @param string $field_name
   *   The machine name of the field to load.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The Access check results.
   */
  public function access(AccountInterface $account, EntityInterface $entity, $field_name) {
    if (!$entity instanceof FieldableEntityInterface) {
      return AccessResult::forbidden();
    }

    // Check if field is a tablefield.
    if ((!$fieldDefinition = $entity->getFieldDefinition($field_name)) || $fieldDefinition->getType() !== 'tablefield') {
      return AccessResult::forbidden();
    }

    return $entity->access('view', $account, TRUE);
  }

  /**
   * Menu callback to export a table as a CSV.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $field_name
   *   The machine name of the field to load.
   * @param string $langcode
   *   The language code specified.
   * @param string $delta
   *   The field delta to load.
   *
   * @return \Symfony\Component\HttpFoundation\StreamedResponse
   *   A streamed response containing tablefield data as a CSV.
   */
  public function exportCsv(EntityInterface $entity, $field_name, $langcode, $delta) {
    $filename = sprintf('%s_%s_%s_%s_%s.csv', $entity->getEntityTypeId(), $entity->id(), $field_name, $langcode, $delta);

    // Tablefield::rationalizeTable($entity->{$field_name}[$delta]->value);.
    $table = $entity->{$field_name}[$delta]->value;
    $separator = \Drupal::config('tablefield.settings')->get('csv_separator');

    $response = new StreamedResponse();
    $response->setCallback(function () use ($table, $separator) {
      ob_clean();
      $handle = fopen('php://output', 'w+');
      if (!empty($table) && $handle) {
        foreach ($table as $row) {
          if (is_array($row) && !is_null($row)) {
            fputcsv($handle, $row, $separator);
          }
        }
      }
      fclose($handle);
    });

    $response->setStatusCode(200);
    $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

    return $response;
  }

}
