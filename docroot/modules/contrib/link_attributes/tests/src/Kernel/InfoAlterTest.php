<?php

namespace Drupal\Tests\link_attributes\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests link_attributes_plugin alterInfo.
 *
 * @group link_attributes
 */
class InfoAlterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'link_attributes',
    'link_attributes_test_alterinfo',
  ];

  /**
   * Tests that plugin definition is changed with alterInfo.
   *
   * Tests that info data is changed after a module that implements
   * hook_link_attributes_plugin_alter() is enabled.
   */
  public function testLinkAttributesManagerInfoAlter() {
    /** @var \Drupal\link_attributes\LinkAttributesManager $linkAttributesManager */
    $linkAttributesManager = $this->container->get('plugin.manager.link_attributes');
    $definition = $linkAttributesManager->getDefinitions();
    $this->assertEquals('textfield', $definition['class']['type'], 'Without altering the plugin definition the class attribute is a textfield.');

    // Set our flag to alter the plugin definition in
    // link_attributes_test_alterinfo module.
    \Drupal::state()->set('link_attributes_test_alterinfo.hook_link_attributes_plugin_alter', 'type_one');
    $linkAttributesManager->clearCachedDefinitions();
    $definition = $linkAttributesManager->getDefinitions();
    $this->assertEquals('select', $definition['class']['type'], 'After altering the plugin definition the class attribute is a select.');
    $this->assertArrayHasKey('button', $definition['class']['options']);
  }

}
