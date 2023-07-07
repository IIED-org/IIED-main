<?php

namespace Drupal\webform_content_creator\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list of Webform Content Creator entities.
 */
class WebformContentCreatorListBuilder extends ConfigEntityListBuilder {

  const TITLE = 'title';

  const WEBFORM = 'webform';

  const ENTITY_TYPE = 'entity_type';

  const BUNDLE = 'bundle';

  /**
   * Constructs the table header.
   *
   * @return array
   *   Table header
   */
  public function buildHeader() {
    $header[self::TITLE] = $this->t('Title');
    $header[self::WEBFORM] = $this->t('Webform');
    $header[self::ENTITY_TYPE] = $this->t('Entity type');
    $header[self::BUNDLE] = $this->t('Bundle');
    return $header + parent::buildHeader();
  }

  /**
   * Constructs the table rows.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Webform content creator entity.
   *
   * @return \Drupal\Core\Entity\EntityListBuilder
   *   A render array structure of fields for this entity.
   */
  public function buildRow(EntityInterface $entity) {
    $webform = \Drupal::entityTypeManager()->getStorage(self::WEBFORM)->load($entity->getWebform());
    $entity_type_id = $entity->getEntityTypeValue();
    $entity_type = \Drupal::entityTypeManager()->getDefinitions()[$entity_type_id];
    $bundle = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type_id)[$entity->getBundleValue()];
    if (!empty($webform) && !empty($entity_type) && !empty($bundle)) {
      $row[self::TITLE] = $entity->get('title') . ' (' . $entity->id() . ')';
      $row[self::WEBFORM] = $webform->label() . ' (' . $entity->getWebform() . ')';
      $row[self::ENTITY_TYPE] = $entity_type->getLabel(). ' (' . $entity->getEntityTypeValue() . ')';
      $row[self::BUNDLE] = $bundle['label'] . ' (' . $entity->getBundleValue() . ')';
      return $row + parent::buildRow($entity);
    }
    return parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity, $type = 'edit') {
    $operations = parent::getDefaultOperations($entity);
    $operations['manage_fields'] = [
      self::TITLE => $this->t('Manage fields'),
      'weight' => 0,
      'url' => Url::fromRoute('entity.webform_content_creator.manage_fields_form', ['webform_content_creator' => $entity->id()]),
    ];

    return $operations;
  }

}
