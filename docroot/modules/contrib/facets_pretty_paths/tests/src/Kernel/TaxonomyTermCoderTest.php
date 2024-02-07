<?php

namespace Drupal\Tests\facets_pretty_paths\Kernel;

use Drupal\facets\Entity\Facet;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the taxonomy term coder plugin.
 *
 * @group facets_pretty_paths
 */
class TaxonomyTermCoderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'token',
    'text',
    'taxonomy',
    'path_alias',
    'pathauto',
    'facets',
    'facets_pretty_paths',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['system', 'pathauto']);
  }

  /**
   * Tests that the TaxonomyTermCoder plugin works correctly.
   */
  public function testTaxonomyTermCoder() {
    $entity_type_manager = $this->container->get('entity_type.manager');

    // Create a vocabulary.
    $entity_type_manager->getStorage('taxonomy_vocabulary')->create([
      'name' => 'Tags',
      'vid' => 'tags',
    ])->save();

    // Create a pathauto pattern for terms.
    $entity_type_manager->getStorage('pathauto_pattern')->create([
      'id' => 'terms',
      'label' => 'Terms',
      'type' => 'canonical_entities:taxonomy_term',
      'pattern' => '[term:name]',
    ])->save();

    // Create a term.
    $entity_type_manager->getStorage('taxonomy_term')->create([
      'name' => 'My term',
      'vid' => 'tags',
    ])->save();

    // Create the coder plugin. It doesn't really need a facet but we should
    // provider one as the plugins expect them.
    $facet_mock = $this->createMock(Facet::class);
    /** @var \Drupal\facets_pretty_paths\Coder\CoderInterface $coder */
    $coder = $this->container->get('plugin.manager.facets_pretty_paths.coder')
      ->createInstance('taxonomy_term_coder', ['facet' => $facet_mock]);

    // We only have 1 term so we know it has the ID of 1. Also, pathauto will
    // turn the title "My title" into "my-title".
    $this->assertEquals('my-term-1', $coder->encode(1));
    $this->assertEquals('1', $coder->decode('my-term-1'));
  }

}
