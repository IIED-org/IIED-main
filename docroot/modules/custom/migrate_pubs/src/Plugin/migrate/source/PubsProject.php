<?php

namespace Drupal\migrate_pubs\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for project content.
 *
 * @MigrateSource(
 *   id = "pubs_project"
 * )
 */
class PubsProject extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $fields = [
      'ProjectNumber',
      'MatchNode',
      'Year',
      'Title',
      'Summary',
      'Link'
    ];
    $query = $this->select('ProjectInfo', 'p')
      ->fields('p', $fields);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'ProjectNumber' => $this->t('ID'),
      'MatchNode' => $this->t('IIED NID'),
      'Year' => $this->t('Year of project'),
      'Title' => $this->t('Title'),
      'Summary' => $this->t('Description'),
      'Link' => $this->t('Linked page'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'MatchNode' => [
        'type' => 'integer',
        'alias' => 'm',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // As explained above, we need to pull the style relationships into our
    // source row here, as an array of 'style' values (the unique ID for
    // the beer_term migration).
    /* $terms = $this->select('migrate_example_beer_topic_node', 'bt')
      ->fields('bt', ['style'])
      ->condition('bid', $row->getSourceProperty('bid'))
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('terms', $terms);
    */
    // As we did for favorite beers in the user migration, we need to explode
    // the multi-value country names.

    return parent::prepareRow($row);
  }

}
