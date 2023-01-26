<?php

namespace Drupal\Tests\datalayer\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Functional test cases for datalayer module integration with Taxonomy.
 *
 * @group DataLayer
 */
class TaxonmyModuleIntegrationTest extends BrowserTestBase {

  use TaxonomyTestTrait, EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  public $profile = 'testing';

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'datalayer',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Module handler to ensure installed modules.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public $moduleHandler;

  /**
   * Module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  public $moduleInstaller;

  /**
   * Vocabulary for testing.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Taxonomy term reference field for testing.
   *
   * @var \Drupal\field\FieldConfigInterface
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->moduleHandler = $this->container->get('module_handler');
    $this->moduleInstaller = $this->container->get('module_installer');

    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'bypass node access',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

  }

  /**
   * Test module works with taxonomy not installed.
   */
  public function testDataLayerNoTaxonomyModule() {
    $this->assertFalse($this->moduleHandler->moduleExists('taxonomy'));
    $this->drupalGet('/admin/config/search/datalayer');
    $assert = $this->assertSession();
    $assert->pageTextContains('Include "data layer helper" library');
    $assert->fieldNotExists('output_terms');

    // Confirm you can submit the form.
    $this->submitForm([], 'Save configuration');
    $assert = $this->assertSession();
    $assert->pageTextContains('The configuration options have been saved.');
  }

  /**
   * Test module works with taxonomy installed.
   */
  public function testDataLayerWithTaxonomyModule() {
    // Install the taxonomy module.
    $this->assertTrue($this->moduleInstaller->install(['taxonomy']));
    $this->rebuildContainer();
    $this->moduleHandler = $this->container->get('module_handler');
    $this->moduleInstaller = $this->container->get('module_installer');
    $this->assertTrue($this->moduleHandler->moduleExists('taxonomy'));

    // Create a new content type and a vocabulary.
    $this->drupalCreateContentType(['type' => 'article']);
    $this->vocabulary = $this->createVocabulary();

    // Add a taxonomy term reference field to the article content type.
    $field_name = 'taxonomy_' . $this->vocabulary->id();
    $handler_settings = [
      'target_bundles' => [
        $this->vocabulary->id() => $this->vocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField('node', 'article', $field_name, NULL, 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $this->field = FieldConfig::loadByName('node', 'article', $field_name);

    // Set the field to be displayed.
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', 'article')
      ->setComponent($field_name, [
        'type' => 'options_select',
      ])
      ->save();
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent($field_name, [
        'type' => 'entity_reference_label',
      ])
      ->save();

    $this->drupalGet('/admin/config/search/datalayer');
    $assert = $this->assertSession();
    $assert->pageTextContains('Include "data layer helper" library');
    $assert->fieldExists('output_terms');
    $assert->fieldExists('vocabs[' . $this->vocabulary->id() . ']');

    // Confirm you can submit the form.
    $this->submitForm([
      'output_terms' => TRUE,
      'vocabs[' . $this->vocabulary->id() . ']' => TRUE,
    ], 'Save configuration');
    $assert = $this->assertSession();
    $assert->pageTextContains('The configuration options have been saved.');
    $assert->checkboxChecked('output_terms');
    $assert->checkboxChecked('vocabs[' . $this->vocabulary->id() . ']');

    // Create a taxonomy term and an article to test.
    $term = $this->createTerm($this->vocabulary);

    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit['body[0][value]'] = $this->randomMachineName();
    $edit[$this->field->getName() . '[]'] = $term->id();
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');

    $taxonomy_dl_string = 'entityTaxonomy":{"' . $this->vocabulary->id() . '":{"1":"' . $term->label() . '"}}';
    $assert = $this->assertSession();
    $assert->responseContains($taxonomy_dl_string);
  }

}
