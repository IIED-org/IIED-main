<?php

// iied_utils/src/Plugin/search_api/processor/ItemBundle.php

namespace Drupal\iied_utils\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds a unified bundle field for nodes and taxonomy terms.
 *
 * @SearchApiProcessor(
 *   id = "item_bundle",
 *   label = @Translation("Item bundle"),
 *   description = @Translation("Adds a combined content type / vocabulary field."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = false,
 *   hidden = false,
 * )
 */
class ItemBundle extends ProcessorPluginBase {

  const FIELD_NAME = 'item_bundle';

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(?DatasourceInterface $datasource = NULL): array {
    // Only expose as a non-datasource-specific property.
    if ($datasource) {
      return [];
    }

    $properties = [];
    $properties[self::FIELD_NAME] = new ProcessorProperty([
      'label' => $this->t('Item bundle'),
      'description' => $this->t('The content type or vocabulary name of this item.'),
      'type' => 'string',
      'processor_id' => $this->getPluginId(),
      'is_list' => FALSE,
    ]);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */

  
public function addFieldValues(ItemInterface $item): void {
  $fields = $this->getFieldsHelper()
    ->filterForPropertyPath(
      $item->getFields(),
      NULL,
      self::FIELD_NAME
    );

  if (empty($fields)) {
    return;
  }

  $entity = $item->getOriginalObject()->getValue();

  if ($entity instanceof \Drupal\node\NodeInterface
    || $entity instanceof \Drupal\taxonomy\TermInterface) {
    foreach ($fields as $field) {
      $field->addValue($entity->bundle());
    }
  }
}
}