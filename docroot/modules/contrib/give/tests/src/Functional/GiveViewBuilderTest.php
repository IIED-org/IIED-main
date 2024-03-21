<?php

namespace Drupal\Tests\give\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests adding give form as entity reference and viewing them through UI.
 *
 * @group give
 */
class GiveViewBuilderTest extends GiveTestBase {

  use StringTranslationTrait;

  /**
   * An administrative user with permission administer give forms.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create Article node type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
      'display_submitted' => FALSE,
    ]);
  }

  /**
   * Tests give view builder functionality.
   */
  public function testGiveViewBuilder() {
    // Create test admin user.
    // @todo having the "administer give" perm it shouldn't need the "manage give forms" but it does. why?
    $this->adminUser = $this->drupalCreateUser([
      'administer content types',
      'access give forms',
      'administer give',
      'manage give forms',
      'administer users',
      'administer account settings',
    ]);

    // Login as admin user.
    $this->drupalLogin($this->adminUser);

    // Create first valid give form.
    $mail = 'simpletest@example.com';
    $this->addGiveForm('test_id', 'test_label', $mail, '', TRUE);
    $this->assertSession()->pageTextContains($this->t('Give form test_label has been added.'));

    $field_name = 'give';
    $entity_type = 'node';
    $bundle_name = 'article';

    // Add a Entity Reference Give Field to Article content type.
    $field_storage = \Drupal::entityTypeManager()
      ->getStorage('field_storage_config')
      ->create([
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'type' => 'entity_reference',
        'settings' => ['target_type' => 'give_form'],
      ]);
    $field_storage->save();
    $field = \Drupal::entityTypeManager()
      ->getStorage('field_config')
      ->create([
        'field_storage' => $field_storage,
        'bundle' => $bundle_name,
        'settings' => [
          'handler' => 'default',
        ],
      ]);
    $field->save();

    // Configure the give reference field form Entity form display.
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.article.default');
    $form_display->setComponent($field_name, [
      'type' => 'options_select',
      'settings' => [
        'weight' => 20,
      ],
    ])
      ->save();

    // Configure the give reference field form Entity view display.
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display */
    $view_display = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load('node.article.default');
    $view_display->setComponent($field_name, [
      'label' => 'above',
      'type' => 'entity_reference_entity_view',
      'weight' => 20,
    ])->save();

    // Display Article creation form.
    $this->drupalGet('node/add/article');
    $title_key = 'title[0][value]';
    $body_key = 'body[0][value]';
    $give_key = 'give';
    // Create article node.
    $edit = [];
    $edit[$title_key] = $this->randomMachineName(8);
    $edit[$body_key] = $this->randomMachineName(16);
    $edit[$give_key] = 'test_id';
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, t('Save'));
    // Check that the node exists in the database.
    $node = $this->drupalGetNodeByTitle($edit[$title_key]);
    $this->drupalGet('node/' . $node->id());
    // Some fields should be present.
    $this->assertSession()->pageTextContains($this->t('Your email address'));
    $this->assertSession()->pageTextContains($this->t('Amount'));
    $this->assertSession()->fieldExists('amount');
  }

}
