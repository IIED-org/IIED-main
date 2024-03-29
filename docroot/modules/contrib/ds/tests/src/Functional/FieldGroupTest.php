<?php

namespace Drupal\Tests\ds\Functional;

use Drupal\Tests\field_group\Functional\FieldGroupTestTrait;

/**
 * Tests for field group integration with Display Suite.
 *
 * @group ds
 */
class FieldGroupTest extends TestBase {

  use FieldGroupTestTrait;

  /**
   * Test tabs.
   */
  public function testFieldPlugin() {

    // Create a node.
    $settings = ['type' => 'article', 'promote' => 1];
    $node = $this->drupalCreateNode($settings);

    // Configure layout.
    $layout = [
      'ds_layout' => 'ds_2col',
    ];
    $layout_assert = [
      'regions' => [
        'left' => '<td colspan="8">' . $this->t('Left') . '</td>',
        'right' => '<td colspan="8">' . $this->t('Right') . '</td>',
      ],
    ];
    $this->dsSelectLayout($layout, $layout_assert);

    $data = [
      'weight' => '1',
      'label' => 'Link',
      'format_type' => 'html_element',
      'format_settings' => [
        'label' => 'Link',
        'element' => 'div',
        'id' => 'wrapper-id',
        'classes' => 'test-class',
      ],
    ];
    $group = $this->createGroup('node', 'article', 'view', 'default', $data);

    $fields = [
      'fields[' . $group->group_name . '][region]' => 'right',
      'fields[body][region]' => 'right',
    ];
    $this->dsConfigureUi($fields);

    $fields = [
      'fields[body][parent]' => $group->group_name,
    ];
    $this->dsConfigureUi($fields);

    $this->drupalGet('node/' . $node->id());

    $elements = $this->xpath("//div[contains(@class, 'group-right')]/div");

    $this->assertTrue($elements[0]->hasClass('test-class'));
    $this->assertEquals('wrapper-id', $elements[0]->getAttribute('id'));
  }

}
