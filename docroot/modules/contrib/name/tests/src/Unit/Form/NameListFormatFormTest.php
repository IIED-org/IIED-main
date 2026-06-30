<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Form\NameListFormatForm;
use Drupal\name\Service\NameFormatterInterface;

/**
 * @coversDefaultClass \Drupal\name\Form\NameListFormatForm
 *
 * @group name
 */
class NameListFormatFormTest extends UnitTestCase {

  /**
   * @covers ::__construct
   */
  public function testConstructorAssignsFormatter(): void {
    $formatter = $this->createMock(NameFormatterInterface::class);
    $form = new NameListFormatForm($formatter);

    $reflection = new \ReflectionProperty(NameListFormatForm::class, 'formatter');
    $reflection->setAccessible(TRUE);
    $this->assertSame($formatter, $reflection->getValue($form));
  }

  /**
   * @covers ::create
   */
  public function testCreateInjectsFormatter(): void {
    $formatter = $this->createMock(NameFormatterInterface::class);
    $container = new ContainerBuilder();
    $container->set('name.formatter', $formatter);

    $form = NameListFormatForm::create($container);
    $this->assertInstanceOf(NameListFormatForm::class, $form);

    $reflection = new \ReflectionProperty(NameListFormatForm::class, 'formatter');
    $reflection->setAccessible(TRUE);
    $this->assertSame($formatter, $reflection->getValue($form));
  }

}
