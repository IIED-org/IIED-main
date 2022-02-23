<?php

namespace Drupal\iied_migrate_d7\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\user\Plugin\migrate\source\d7\User;

/**
 * Source plugin for blog content.
 *
 * @MigrateSource(
 *   id = "iied_d7_user_redirect",
 *   source_module = "user"
 * )
 */
class D7UserRedirect extends User {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    // UID 9296 good for testing. And 97 / 53
    // $query->condition('u.uid', '53');
    // $query->condition('u.uid', '354');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    // Set the status_code to default to 301.
    $row->setSourceProperty('status_code', '301');

    // Get the original user path alias.
    $uid = $row->getSourceProperty('uid');
    $original_user_alias = $this->database->query('SELECT alias FROM {url_alias} WHERE source = :source', [':source' => 'user/' . $uid])->fetchField();
    $row->setSourceProperty('source', $original_user_alias);

    // Get the new destination person taxonomy term.
    $db = \Drupal\Core\Database\Database::getConnection();
    $query = $db->select('migrate_map_iied_d7_terms_person', 'mm');
    $query->fields('mm', ['destid1']);
    $query->condition('sourceid1', $uid);
    $target_term_id = $query->execute()->fetchField();
    $row->setSourceProperty('redirect', 'taxonomy/term/' . $target_term_id);

    return parent::prepareRow($row);
  }

}
