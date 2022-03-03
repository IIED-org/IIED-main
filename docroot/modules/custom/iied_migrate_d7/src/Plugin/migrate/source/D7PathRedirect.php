<?php

namespace Drupal\iied_migrate_d7\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 path redirect source from database.
 *
 * @MigrateSource(
 *   id = "iied_d7_path_redirect",
 *   source_module = "redirect"
 * )
 */
class D7PathRedirect extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Select path redirects.
    $query = $this->select('redirect', 'p')->fields('p');
    // $query->condition('rid', '41');
    // $query->condition('rid', '1563');
    // The source redirect for bw2018 has two entries, so causes an error.
    $query->condition('p.source', 'bw2018', '<>');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    static $default_status_code;
    if (!isset($default_status_code)) {
      $default_status_code = unserialize($this->getDatabase()
        ->select('variable', 'v')
        ->fields('v', ['value'])
        ->condition('name', 'redirect_default_status_code')
        ->execute()
        ->fetchField());
    }
    $current_status_code = $row->getSourceProperty('status_code');
    $status_code = $current_status_code != 0 ? $current_status_code : '301';
    $row->setSourceProperty('status_code', $status_code);

    // Extract redirect target node id.
    $redirect = $row->getSourceProperty('redirect');

    if (substr($redirect, 0, 4) == 'node') {
      $original_node_id = substr($redirect, 5, strlen($redirect));
      // Get original node type.
      $original_node_type = $this->database->query('SELECT type FROM {node} WHERE nid = :nid', [':nid' => $original_node_id])->fetchField();
      $new_node_id = $this->getNewNodeId($original_node_id, $original_node_type);
      if ($new_node_id) {
        $row->setSourceProperty('redirect', 'node/' . $new_node_id);
      }
      else {
        return FALSE;
      }
    }
    elseif (substr($redirect, 0, 13) == 'taxonomy/term') {

      $term_id = substr($redirect, 14, strlen($redirect));
      $vid = $this->database->query('SELECT vid FROM {taxonomy_term_data} WHERE tid = :tid', [':tid' => $term_id])->fetchField();
      // Either collection (21), tag (15)
      if ($vid == '15') {
        $db = \Drupal\Core\Database\Database::getConnection();
        $query = $db->select('migrate_map_iied_tags', 'mm');
        $query->fields('mm', array('destid1'));
        $query->condition('sourceid1', 'https://www.iied.org/taxonomy/term/' . $term_id);
        $target_term_id = $query->execute()->fetchField();
        $row->setSourceProperty('redirect', 'taxonomy/term/' . $target_term_id);
      }
      elseif ($vid == '21') {
        // vid 2, collection terms.
        $db = \Drupal\Core\Database\Database::getConnection();
        $query = $db->select('migrate_map_iied_d7_terms_collection', 'mm');
        $query->fields('mm', array('destid1'));
        $query->condition('sourceid1', $term_id);
        $target_term_id = $query->execute()->fetchField();
        $row->setSourceProperty('redirect', 'taxonomy/term/' . $target_term_id);
      }

    }
    elseif (substr($redirect, 0, 5) == 'user/') {
      $user_id = substr($redirect, 5, strlen($redirect));
      $db = \Drupal\Core\Database\Database::getConnection();
      $query = $db->select('migrate_map_iied_d7_terms_person', 'mm');
      $query->fields('mm', array('destid1'));
      $query->condition('sourceid1', $user_id);
      $target_term_id = $query->execute()->fetchField();
      $row->setSourceProperty('redirect', 'taxonomy/term/' . $target_term_id);
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getNewNodeId($original_node_id, $type) {
    $type_maps = [
      'article' => 'migrate_map_iied_d7_articles',
      'blog' => 'migrate_map_iied_d7_blogs',
      'event' => 'migrate_map_iied_d7_events',
      'project' => 'migrate_map_iied_d7_projects',
      'press' => 'migrate_map_iied_d7_news_press',
      'media_release' => 'migrate_map_iied_d7_news_media',
    ];
    if (in_array($type, array_keys($type_maps))) {
      // Lookup node in migrate_map table.
      $db = \Drupal\Core\Database\Database::getConnection();
      $query = $db->select($type_maps[$type], 'mm');
      $query->fields('mm', array('destid1'));
      $query->condition('sourceid1', $original_node_id);
      $new_node_id = $query->execute()->fetchField();
      return $new_node_id;
    }
    else {
      return FALSE;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'rid' => $this->t('Redirect ID'),
      'hash' => $this->t('Hash'),
      'type' => $this->t('Type'),
      'uid' => $this->t('UID'),
      'source' => $this->t('Source'),
      'source_options' => $this->t('Source Options'),
      'redirect' => $this->t('Redirect'),
      'redirect_options' => $this->t('Redirect Options'),
      'language' => $this->t('Language'),
      'status_code' => $this->t('Status Code'),
      'count' => $this->t('Count'),
      'access' => $this->t('Access'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['rid']['type'] = 'integer';
    return $ids;
  }

}
