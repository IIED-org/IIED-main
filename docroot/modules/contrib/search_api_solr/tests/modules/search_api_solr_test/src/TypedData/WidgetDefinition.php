<?php

<<<<<<< HEAD
// phpcs:ignoreFile SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Drupal\search_api_solr_test\TypedData;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\ListDataDefinition;
=======
namespace Drupal\search_api_solr_test\TypedData;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
>>>>>>> parent of 3b9f439507 (remove gitignored directories)

/**
 * A typed data definition class for describing widgets.
 */
class WidgetDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
<<<<<<< HEAD
    $this->propertyDefinitions['widget_types'] = ListDataDefinition::create('string')
=======
    $this->propertyDefinitions['widget_types'] = \Drupal::typedDataManager()
      ->createListDataDefinition('string')
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
      ->setLabel('Widget Types');
    return $this->propertyDefinitions;
  }

}
