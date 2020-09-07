<?php

namespace Drupal\migrate_pubs\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for pubs content.
 *
 * @MigrateSource(
 *   id = "pubs_node"
 * )
 */
class PubsNode extends SqlBase {

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
      // @TODO: Add rest of pubs fields
      'ProductCode',
      'LitCode',
      'Status',
      'ReportingCode1',
      'ReportingCode2',
      'ReportFin1',
      'ReportAgency1',
      'ReportFin2',
      'ReportAgency2',
      'ReportFin3',
      'ReportAgency3',
      'Title',
      'ShortTitle',
      'AuthorList',
      'Publisher',
      'DeptList',
      'Abstract',
      'Theme',
      'Theme2',
      'Team',
      'Keywords',
      'DocType',
      'ProjectNumber',
      'AreaList',
      'Language',
      'MonthPublished',
      'ISBN13',
      'ISSN',
      'SeriesCode',
      'SeriesItemCode',
      'SourcePublication',
      'JournalRef',
      'PubPages',
      'LinkIIEDURL',
      'LinkMoreURL'
    ];
    $query = $this->select('Publications', 'p')
      ->fields('p', $fields);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'ProductCode' => $this->t('Publication ID'),
      'LitCode' => $this->t('P, S or X'),
      'Status' => $this->t('Status A or N only'),
      'ReportingCode1' => $this->t('ReportingCode1'),
      'ReportingCode2' => $this->t('ReportingCode2'),
      'ReportFin1' => $this->t('ReportFin1'),
      'ReportAgency1' => $this->t('ReportAgency1'),
      'ReportFin2' => $this->t('ReportFin2'),
      'ReportAgency2' => $this->t('ReportAgency2'),
      'ReportFin3' => $this->t('ReportFin3'),
      'ReportAgency3' => $this->t('ReportAgency3'),
      'Title' => $this->t('Title of publication'),
      'ShortTitle' => $this->t('Short title'),
      'AuthorList' => $this->t('Authors: multiple values, delimited by pipe'),
      'Publisher' => $this->t('Sometimes delimited by commas'),
      'DeptList' => $this->t('DepList: multiple values, delimited by pipe'),
      'Abstract' => $this->t('Abstract for this publication'),
      'Theme' => $this->t('Primary theme'),
      'Theme2' => $this->t('Secondary theme'),
      'Team'  => $this->t('Team code'),
      'Keywords' => $this->t('Tags, delimited by pipe'),
      'DocType' => $this->t('Doctype lookup'),
      'ProjectNumber' => $this->t('Legacy project ID'),
      'AreaList' => $this->t('List of countries'),
      'Language' => $this->t('Publication language'),
      'MonthPublished' => $this->t('Year and month published'),
      'ISBN13' => $this->t('ISBN number'),
      'ISSN' => $this->t('ISSN number'),
      'SeriesCode' => $this->t('Series code'),
      'SeriesItemCode' => $this->t('Series item code'),
      'SourcePublication' => $this->t('Source publication'),
      'JournalRef' => $this->t('Source publication reference'),
      'PubPages' => $this->t('Number of pages'),
      'LinkIIEDURL' => $this->t('IIED link'),
      'LinkMoreURL' => $this->t('External link'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'ProductCode' => [
        'type' => 'string',
        'alias' => 'p',
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
    if ($value = $row->getSourceProperty('AuthorList')) {
      $row->setSourceProperty('AuthorList', explode('|', substr($value,1,strlen($value)-2)));
    }
    if ($value = $row->getSourceProperty('Keywords')) {
     $row->setSourceProperty('Keywords', explode('|', substr($value,1,strlen($value)-2)));
    }
    if ($value = $row->getSourceProperty('DeptList')) {
     $row->setSourceProperty('DeptList', explode('|', substr($value,1,strlen($value)-2)));
    }
    if ($value = $row->getSourceProperty('Abstract')) {
     $row->setSourceProperty('Abstract', str_replace('~~', PHP_EOL . PHP_EOL, $value));
    }
    if ($value = $row->getSourceProperty('AreaList')) {
     $row->setSourceProperty('AreaList', explode('|', substr($value,1,strlen($value)-2)));
    }
    return parent::prepareRow($row);
  }

}
