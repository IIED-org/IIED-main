<?php

namespace Drupal\migrate_pubs\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for themes content.
 *
 * @MigrateSource(
 *   id = "pubs_theme"
 * )
 */
class PubsTheme extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // An important point to note is that your query *must* return a single row
    // for each item to be imported. Here we might be tempted to add a join to
    // migrate_example_beer_topic_node in our query, to pull in the
    // relationships to our categories. Doing this would cause the query to
    // return multiple rows for a given node, once per related value, thus
    // processing the same node multiple times, each time with only one of the
    // multiple values that should be imported. To avoid that, we simply query
    // the base node data here, and pull in the relationships in prepareRow()
    // below.
    $fields = [
      'Code',
      'ShortName',
      'LongName'
    ];
    $query = $this->select('Themes', 't')
      ->fields('t', $fields);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'Code' => $this->t('Key'),
      'ShortName' => $this->t('Theme name'),
      'LongName' => $this->t('Description'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'Code' => [
        'type' => 'string',
        'alias' => 'a',
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
