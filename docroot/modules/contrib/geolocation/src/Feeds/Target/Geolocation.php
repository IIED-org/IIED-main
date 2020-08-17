<?php

namespace Drupal\geolocation\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\TargetValidationException;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a geolocation field mapper.
 *
 * @FeedsTarget(
 *   id = "geolocation_feeds_target",
 *   field_types = {"geolocation"}
 * )
 */
class Geolocation extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('lat')
      ->addProperty('lng');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    if (isset($values)) {
      if (isset($values['lat']) && isset($values['lng'])) {
        $values['lat'] = floatval($values['lat']);
        $values['lng'] = floatval($values['lng']);
        $values['lat_sin'] = sin(deg2rad($values['lat']));
        $values['lat_cos'] = cos(deg2rad($values['lat']));
        $values['lng_rad'] = deg2rad($values['lng']);
      }
      return $values;
    }
    else {
      throw new EmptyFeedException();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValues(array $values) {
    $return = [];
    foreach ($values as $delta => $columns) {
      try {
        $this->prepareValue($delta, $columns);
        $return[] = $columns;
      }
      catch (EmptyFeedException $e) {
        // Nothing wrong here.
      }
      catch (TargetValidationException $e) {
        // Validation failed.
        \Drupal::messenger()->addError($e->getMessage());
      }
    }

    return $return;
  }

}
