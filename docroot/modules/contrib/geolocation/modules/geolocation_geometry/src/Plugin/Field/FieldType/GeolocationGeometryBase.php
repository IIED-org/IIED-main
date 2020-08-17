<?php

namespace Drupal\geolocation_geometry\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Class GeolocationGeometryBase.
 *
 * @package Drupal\geolocation_geometry\Plugin\Field\FieldType
 */
abstract class GeolocationGeometryBase extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'geometry' => [
          'description' => 'Stores the geometry',
          'type' => 'text',
          'mysql_type' => 'geometry',
          'pgsql_type' => 'geometry',
          'size' => 'big',
          'not null' => FALSE,
        ],
        'wkt' => [
          'description' => 'Stores the geometry as Well Known Text',
          'type' => 'text',
          'size' => 'big',
          'not null' => TRUE,
        ],
        'geojson' => [
          'description' => 'Stores the geometry as GeoJSON',
          'type' => 'text',
          'size' => 'big',
          'not null' => TRUE,
        ],
        'data' => [
          'description' => 'Serialized array of additional data.',
          'type' => 'blob',
          'size' => 'big',
          'not null' => FALSE,
          'serialize' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $geom_type = explode("_", $field_definition->getType())[2];

    $properties['geometry'] = DataDefinition::create('string')
      ->setComputed('true')
      ->setLabel(t('Geometry'));
    $properties['wkt'] = DataDefinition::create('string')->setLabel(t('WKT'))
      ->addConstraint('GeometryType', ['geometryType' => $geom_type, 'type' => 'WKT']);
    $properties['geojson'] = DataDefinition::create('string')->setLabel(t('GeoJSON'))
      ->addConstraint('GeometryType', ['geometryType' => $geom_type, 'type' => 'GeoJSON']);
    $properties['data'] = MapDataDefinition::create()->setLabel(t('Meta data'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    parent::postSave($update);

    $entity = $this->getEntity();
    $entity_storage = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId());

    if (!is_a($entity_storage, '\Drupal\Core\Entity\Sql\SqlContentEntityStorage')) {
      return FALSE;
    }

    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $entity_storage */
    $table_mapping = $entity_storage->getTableMapping();
    $field_storage_definition = $this->getFieldDefinition()->getFieldStorageDefinition();

    if ($entity->getEntityType()->isRevisionable()) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $query = \Drupal::database()->update($table_mapping->getDedicatedRevisionTableName($field_storage_definition));
      if (!empty($this->values['wkt'])) {
        $query->expression($field_storage_definition->getName() . '_geometry', 'ST_GeomFromText(' . $field_storage_definition->getName() . '_wkt, 4326)');
        $query->expression($field_storage_definition->getName() . '_geojson', 'ST_AsGeoJSON(ST_GeomFromText(' . $field_storage_definition->getName() . '_wkt, 4326))');
      }
      elseif (!empty($this->values['geojson'])) {
        $query->expression($field_storage_definition->getName() . '_geometry', 'ST_GeomFromGeoJSON(' . $field_storage_definition->getName() . '_geojson)');
        $query->expression($field_storage_definition->getName() . '_wkt', 'ST_AsText(ST_GeomFromGeoJSON(' . $field_storage_definition->getName() . '_geojson))');
      }
      if (empty($this->values['data'])) {
        $query->fields([$field_storage_definition->getName() . '_data' => serialize(NULL)]);
      }
      $query->condition('entity_id', $entity->id());
      $query->condition('revision_id', $entity->getRevisionId());
      $query->condition('bundle', $entity->bundle());
      $query->condition('delta', $this->getName());
      $query->condition('langcode', $this->getLangcode());
      $query->execute();
    }

    $query = \Drupal::database()->update($table_mapping->getDedicatedDataTableName($field_storage_definition));
    if (!empty($this->values['wkt'])) {
      $query->expression($field_storage_definition->getName() . '_geometry', 'ST_GeomFromText(' . $field_storage_definition->getName() . '_wkt, 4326)');
      $query->expression($field_storage_definition->getName() . '_geojson', 'ST_AsGeoJSON(ST_GeomFromText(' . $field_storage_definition->getName() . '_wkt, 4326))');
    }
    elseif (!empty($this->values['geojson'])) {
      $query->expression($field_storage_definition->getName() . '_geometry', 'ST_GeomFromGeoJSON(' . $field_storage_definition->getName() . '_geojson)');
      $query->expression($field_storage_definition->getName() . '_wkt', 'ST_AsText(ST_GeomFromGeoJSON(' . $field_storage_definition->getName() . '_geojson))');
    }
    if (empty($this->values['data'])) {
      $query->fields([$field_storage_definition->getName() . '_data' => serialize(NULL)]);
    }
    $query->condition('entity_id', $entity->id());
    $query->condition('bundle', $entity->bundle());
    $query->condition('delta', $this->getName());
    $query->condition('langcode', $this->getLangcode());
    $query->execute();

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $latitude = rand(-89, 90) - rand(0, 999999) / 1000000;
    $longitude = rand(-179, 180) - rand(0, 999999) / 1000000;
    $values['wkt'] = 'POINT (' . $latitude . ' ' . $longitude . ')';
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return (empty($this->get('wkt')->getValue()) && empty($this->get('geojson')->getValue()));
  }

}
