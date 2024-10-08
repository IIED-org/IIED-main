<?php

declare(strict_types=1);

namespace Drupal\Tests\path\Kernel;

use Drupal\content_translation_test\Entity\EntityTestTranslatableUISkip;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests path alias deletion when there is no canonical link template.
 *
 * @group path
 */
class PathNoCanonicalLinkTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path',
    'content_translation_test',
    'language',
    'entity_test',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_mul');

    // Adding german language.
    ConfigurableLanguage::createFromLangcode('de')->save();

    $this->config('language.types')->setData([
      'configurable' => ['language_interface'],
      'negotiation' => ['language_interface' => ['enabled' => ['language-url' => 0]]],
    ])->save();
  }

  /**
   * Tests for no canonical link templates.
   */
  public function testNoCanonicalLinkTemplate(): void {
    $entity_type = EntityTestTranslatableUISkip::create([
      'name' => 'name english',
      'language' => 'en',
    ]);
    $entity_type->save();

    $entity_type->addTranslation('de', ['name' => 'name german']);
    $entity_type->save();
    $this->assertCount(2, $entity_type->getTranslationLanguages());

    $entity_type->removeTranslation('de');
    $entity_type->save();
    $this->assertCount(1, $entity_type->getTranslationLanguages());
  }

}
