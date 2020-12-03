<?php

namespace Drupal\Tests\rh_taxonomy\Functional;

use Drupal\Core\Url;
use Drupal\Tests\rabbit_hole\Functional\RabbitHoleBehaviorSettingsFormTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Test the functionality of the rabbit hole form additions to the taxonomy.
 *
 * @group rh_taxonomy
 */
class TaxonomyBehaviorSettingsFormTest extends RabbitHoleBehaviorSettingsFormTestBase {

  use TaxonomyTestTrait;

  /**
   * Test taxonomy vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $bundle;

  /**
   * {@inheritdoc}
   */
  protected $entityType = 'taxonomy_term';

  /**
   * {@inheritdoc}
   */
  protected $bundleEntityTypeName = 'taxonomy_vocabulary';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rh_taxonomy', 'taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundle() {
    $this->bundle = $this->createVocabulary();
    return $this->bundle->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundleFormSubmit($action, $override) {
    $this->drupalLogin($this->adminUser);
    $edit = [
      'name' => $this->randomString(),
      'vid' => mb_strtolower($this->randomMachineName()),
      'rh_action' => $action,
      'rh_override' => $override,
    ];
    $this->drupalGet('/admin/structure/taxonomy/add');
    $this->assertRabbitHoleSettings();
    $this->submitForm($edit, 'Save');
    return $edit['vid'];
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity($action = NULL) {
    $values = [];
    if (isset($action)) {
      $values['rh_action'] = $action;
    }
    return $this->createTerm($this->bundle, $values)->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function getCreateEntityUrl() {
    return Url::fromRoute('entity.taxonomy_term.add_form', ['taxonomy_vocabulary' => $this->bundle->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditEntityUrl($id) {
    return Url::fromRoute('entity.taxonomy_term.edit_form', ['taxonomy_term' => $id]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditBundleUrl($bundle) {
    return Url::fromRoute('entity.taxonomy_vocabulary.edit_form', ['taxonomy_vocabulary' => $bundle]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdminPermissions() {
    return ['administer taxonomy', 'access taxonomy overview'];
  }

}
