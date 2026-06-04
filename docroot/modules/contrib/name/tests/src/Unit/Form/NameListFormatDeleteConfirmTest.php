<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Form\NameListFormatDeleteConfirm;

/**
 * @coversDefaultClass \Drupal\name\Form\NameListFormatDeleteConfirm
 *
 * @group name
 */
class NameListFormatDeleteConfirmTest extends UnitTestCase {

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
      ->willReturn('My List Format');

    $form = new NameListFormatDeleteConfirm();
    $form->setStringTranslation($this->getStringTranslationStub());
    $form->setEntity($entity);

    $question = (string) $form->getQuestion();
    $this->assertStringContainsString('My List Format', $question);

    $this->assertSame('Delete', (string) $form->getConfirmText());
    $this->assertSame(
      'name.name_list_format_list',
      $form->getCancelUrl()->getRouteName()
    );
  }

}
