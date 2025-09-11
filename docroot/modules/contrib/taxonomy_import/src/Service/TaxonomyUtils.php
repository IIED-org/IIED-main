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
  public function updateTerm($vid, $term, $parentId, $description) {
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
  public function createTerm($vid, $name, $parentId, $description) {
    $term = $this->entityTypeManager->getStorage('taxonomy_term')->create([
      'parent' => [$parentId],
      'name' => $name,
      'description' => $description,
      'vid' => $vid,
    ]);

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
  public function saveTerms($vid, $rows) {
    foreach ($rows as $row) {
      $term = $this->loadTerm($vid, $row['name']);
      $parent = !empty($row['parent']) ? $this->loadTerm($vid, $row['parent']) : NULL;
      $parentId = $parent ? $parent->id() : 0;

      if ($term) {
        $this->updateTerm($vid, $term, $parentId, $row['description']);
      }
      else {
        $this->createTerm($vid, $row['name'], $parentId, $row['description']);
      }
    }
  }

}
