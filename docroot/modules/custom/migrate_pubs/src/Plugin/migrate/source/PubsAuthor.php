<?php

namespace Drupal\migrate_pubs\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for pubs content.
 *
 * @MigrateSource(
 *   id = "pubs_author"
 * )
 */
class PubsAuthor extends SqlBase {

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
      'Author',
      'IsCanonical',
    ];
    $query = $this->select('AuthorMap', 'a')
      ->fields('a', $fields);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'Author' => $this->t('Author full name'),
      'Surname' => $this->t('Last name of author for sorting'),
      'Given' => $this->t('Given name of author'),
      'Middle' =>  $this->t('Middle name(s) of author'),
      'IsCanonical' => $this->t('Canonical version of name')
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'Author' => [
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
    if ($row->getSourceProperty('IsCanonical') != 1 ) {
       return FALSE;
    }

    elseif ($value = $row->getSourceProperty('Author')) {
      $output = preg_replace_callback("/(&#[0-9]+;)/", function($m) {
        return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
        }, $value);
      $pieces = explode(' ', $output);
      $last_word = array_pop($pieces);
      $first_word = array_shift($pieces);
      $middle = implode(' ',$pieces);
      $row->setSourceProperty('Author', $output);
      $row->setSourceProperty('Surname', $last_word);
      $row->setSourceProperty('Given', $first_word);
      if (null != $middle) {
        $row->setSourceProperty('Middle', $middle);
      }
    }
    return parent::prepareRow($row);
  }

}
