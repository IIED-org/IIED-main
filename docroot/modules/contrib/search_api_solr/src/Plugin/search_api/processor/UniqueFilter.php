<?php

namespace Drupal\search_api_solr\Plugin\search_api\processor;

<<<<<<< HEAD
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiProcessor;
=======
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\FieldsProcessorPluginBase;

/**
 * Strips HTML tags from fulltext fields and decodes HTML entities.
<<<<<<< HEAD
 */
#[SearchApiProcessor(
  id: 'unique_filter',
  label: new TranslatableMarkup('Unique values filter'),
  description: new TranslatableMarkup('Ensures unique values for multi-valued fields'),
  stages: [
    'pre_index_save' => 0,
    'preprocess_index' => -15,
    'preprocess_query' => -15,
  ],
)]
=======
 *
 * @SearchApiProcessor(
 *   id = "unique_filter",
 *   label = @Translation("Unique values filter"),
 *   description = @Translation("Ensures unique values for multi-valued fields"),
 *   stages = {
 *     "pre_index_save" = 0,
 *     "preprocess_index" = -15,
 *     "preprocess_query" = -15,
 *   }
 * )
 */
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
class UniqueFilter extends FieldsProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function testType($type) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function processField(FieldInterface $field) {
    parent::processField($field);

    $values = array_unique($field->getValues());
    $field->setValues($values);
  }

}
