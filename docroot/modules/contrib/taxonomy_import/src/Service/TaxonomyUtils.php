<?php

namespace Drupal\taxonomy_import\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Our utils.
 */
class TaxonomyUtils implements TaxonomyUtilsInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * OQUtils constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   *
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function loadTerm($vid, $name) {
    $ary = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'vid' => $vid,
      'name' => $name,
    ]);

    return !empty($ary) ? reset($ary) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function updateTerm($vid, $term, $parentId, $description, $rowData, $termCustomFields) {
    $needsSave = FALSE;

    if ($parentId) {
      $parentIds = $this->getTermParentIds($term);
      if (!in_array($parentId, $parentIds)) {
        $parentIds[] = $parentId;
        $term->set('parent', $parentIds);
        $needsSave = TRUE;
      }
    }

    if ($term->getDescription() != $description) {
      $term->setDescription($description);
      $needsSave = TRUE;
    }

    foreach($termCustomFields as $termCustomField) {
      $term->set($termCustomField, $rowData[$termCustomField]);
      $needsSave = TRUE;
    }

    return $needsSave ? $term->save() : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function createVocabulary($vocabularyName) {
    // Converting to machine name.
    $machine_readable = strtolower($vocabularyName);
    // Vocabulary machine name.
    $vid = preg_replace('@[^a-z0-9_]+@', '_', $machine_readable);
    // Creating new vocabulary with the field value.
    $vocabularies = Vocabulary::loadMultiple();

    if (isset($vocabularies[$vid])) {
      return $vocabularies[$vid];
    }

    $vocabulary = Vocabulary::create([
      'vid' => $vid,
      'machine_name' => $vid,
      'name' => $name,
      'description' => '',
    ]);

    return $vocabulary->save() ? $vocabulary : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function createTerm($vid, $name, $parentId, $description, $rowData, $termCustomFields) {
    $term = $this->entityTypeManager->getStorage('taxonomy_term')->create([
      'parent' => [$parentId],
      'name' => $name,
      'description' => $description,
      'vid' => $vid,
    ]);

    foreach ($termCustomFields as $termCustomField) {
      $term->set($termCustomField, $rowData[$termCustomField]);
    }

    return $term->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getTermParentIds($term) {
    $ret = [];
    foreach ($term->get('parent') as $par) {
      $temp = $par->get('entity');
      if (!$temp) {
        continue;
      }

      $temp = $temp->getTarget();
      if (!$temp) {
        continue;
      }

      $temp = $temp->getValue();
      if (!$temp) {
        continue;
      }

      $ret[] = $temp->id();
    }

    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function saveTerms($vid, $rows, $forceNewTerms = TRUE) {
    foreach ($rows as $row) {
      $term = $this->loadTerm($vid, $row['name']);

      //Update to load the most recent term matching term name for parent.
      $parentId = 0;
      if (!empty($row['parent'])) {
        $parents = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(
          [
            'name' => $row['parent'],
            'vid' => $vid,
          ]
        );
        if (count($parents) > 0) {
          \Drupal::logger('taxonomy_import')->debug('parent terms found with the name %name: <br>%parents<br><br>last tid is %tid',
            [
              '%name' => $row['parent'],
              '%parents' => json_encode($parents, TRUE),
              '%tid' => key($parents),
            ]
          );
        }

        $parentId = key($parents);
      }

      // Get the field names from the source file.
      $fields_detected = array_keys($row);
      \Drupal::logger('taxonomy_import')->debug('fields detected from xml: %fieldlist', [
        '%fieldlist' => json_encode($fields_detected, TRUE),
      ]);
      $system_keys = ['name', 'parent', 'description'];

      // Refine list of field names to only the custom fields.
      foreach ($system_keys as $system_key) {
        if (($key = array_search($system_key, $fields_detected)) !== FALSE) {
          array_splice($fields_detected, $key, 1);
        }
      }

      // Refine list of field names to only custom fields that exist for the vocabulary.
      $fields_not_detected = [];
      foreach ($fields_detected as $field_detected) {
        $vocabulary_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('taxonomy_term', $vid);
        if (isset($vocabulary_fields[$field_detected]) === FALSE) {
          $key = array_search($field_detected, $fields_detected);
          array_splice($fields_detected, $key, 1);
          array_push($fields_not_detected, $field_detected);
        }
      }
      \Drupal::logger('taxonomy_import')->debug('custom fields not in Drupal:<br>%fieldsMissing<br><br>custom fields matched in Drupal:<br>%fieldlist', [
        '%fieldsMissing' => json_encode($fields_not_detected, TRUE),
        '%fieldlist' => json_encode($fields_detected, TRUE),
      ]);

      $termCustomFields = [];
      if (count($fields_detected) > 0) {
        foreach ($fields_detected as $custom_field) {
          array_push($termCustomFields, $custom_field);
        }
      }
      if ($term && !$forceNewTerms) {
        $this->updateTerm($vid, $term, $parentId, $row['description'], $row, $termCustomFields);
      }
      else {
        $this->createTerm($vid, $row['name'], $parentId, $row['description'], $row, $termCustomFields);
      }
    }
  }

}
