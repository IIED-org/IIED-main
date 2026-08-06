<?php

namespace Drupal\search_api_solr\Plugin\search_api\processor;

<<<<<<< HEAD
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiProcessor;
=======
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api_solr\Plugin\search_api\data_type\value\DateRangeValue;

/**
 * Add date ranges to the index.
<<<<<<< HEAD
 */
#[SearchApiProcessor(
  id: 'solr_date_range',
  label: new TranslatableMarkup('Date ranges'),
  description: new TranslatableMarkup('Date ranges.'),
  stages: [
    'preprocess_index' => 0,
  ],
  locked: TRUE,
  hidden: TRUE,
)]
=======
 *
 * @SearchApiProcessor(
 *   id = "solr_date_range",
 *   label = @Translation("Date ranges"),
 *   description = @Translation("Date ranges."),
 *   stages = {
 *     "preprocess_index" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
class DateRange extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {
    foreach ($items as $item) {
      /** @var \Drupal\search_api\Item\FieldInterface $field */
      foreach ($item->getFields() as $field) {
        if ('solr_date_range' === $field->getType()) {
          $values = [];
          $required_properties = [
            $item->getDatasourceId() => [
              $field->getPropertyPath() . ':value' => 'start',
              $field->getPropertyPath() . ':end_value' => 'end',
            ],
          ];
          $item_values = $this->getFieldsHelper()->extractItemValues([$item], $required_properties);
          foreach ($item_values as $dates) {
            $start_dates = $dates['start'];
            $end_dates = $dates['end'];

            for ($i = 0, $n = count($start_dates); $i < $n; $i++) {
              $values[$i] = new DateRangeValue(
                $start_dates[$i],
                $end_dates[$i]
              );
            }
          }
          if (!empty($values)) {
            $field->setValues($values);
          }
        }
      }
    }
  }

}
