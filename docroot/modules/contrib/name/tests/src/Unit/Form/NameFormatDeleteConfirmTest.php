<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Form\NameFormatDeleteConfirm;

/**
 * @coversDefaultClass \Drupal\name\Form\NameFormatDeleteConfirm
 *
 * @group name
 */
class NameFormatDeleteConfirmTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::getQuestion
   * @covers ::getConfirmText
   * @covers ::getCancelUrl
   */
  public function testGettersUseEntityLabelAndListRoute(): void {
    $entity = $this->createMock(EntityInterface::class);
    $entity->expects($this->atLeastOnce())
      ->method('label')
      ->willReturn('Custom Test Format');

    $form = new NameFormatDeleteConfirm();
    $form->setStringTranslation($this->getStringTranslationStub());
    $form->setEntity($entity);

    $question = (string) $form->getQuestion();
    $this->assertStringContainsString('Custom Test Format', $question);

    $this->assertSame('Delete', (string) $form->getConfirmText());
    $this->assertSame('name.name_format_list', $form->getCancelUrl()->getRouteName());
  }

}
