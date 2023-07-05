<?php

namespace Drupal\Tests\isbn\Functional;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests Isbn field functionality.
 *
 * @group isbn
 */
class IsbnFieldTest extends IsbnTestBase {

  /**
   * Tests the isbn field.
   */
  public function testIsbnField() {
    $field_name = $this->fieldStorage->getName();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][value]", '');
    $this->assertSession()->pageTextContains($this->field->label());

    // Submit a valid isbn value and ensure it is accepted.
    $value = '0123456789';
    $edit = [
      "{$field_name}[0][value]" => $value,
    ];
    $this->submitForm($edit, 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertSession()->pageTextContains('entity_test ' . $id . ' has been created.');
    $this->assertSession()->responseContains($value);

    // Verify that a isbn number is displayed.
    $entity = EntityTest::load($id);
    $display = $display_repository->getViewDisplay($entity->getEntityTypeId(), $entity->bundle(), 'full');
    $content = $display->build($entity);
    $rendered_content = (string) $renderer->renderRoot($content);
    $this->assertStringContainsString('0123456789', $rendered_content);

    // Test with the "isbn_formatted_formatter" formatter.
    $display_repository->getViewDisplay('entity_test', 'entity_test', 'full')
      ->setComponent($field_name, [
        'type' => 'isbn_formatted_formatter',
      ])
      ->save();
    $display = $display_repository->getViewDisplay($entity->getEntityTypeId(), $entity->bundle(), 'full');
    $content = $display->build($entity);
    $rendered_content = (string) $renderer->renderRoot($content);
    $this->assertStringContainsString('0-12-345678-9', $rendered_content);
  }

  /**
   * Tests that an invalid ISBN value does not get accepted.
   */
  public function testIsbnFieldValidation() {
    $field_name = $this->fieldStorage->getName();

    $this->drupalGet('entity_test/add');
    // Try to submit an invalid isbn value.
    $value = '012345678X';
    $edit = [
      "{$field_name}[0][value]" => $value,
    ];
    $this->submitForm($edit, 'Save');

    // Assert that a validation error message is displayed.
    $this->assertSession()->pageTextContains('"012345678X" isn\'t a valid ISBN number.');
    $this->assertSession()->pageTextNotContains('has been created.');
  }

}
