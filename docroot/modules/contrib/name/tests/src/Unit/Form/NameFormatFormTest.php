<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Form\NameFormatForm;
use Drupal\name\Service\NameFormatParserInterface;

/**
 * @coversDefaultClass \Drupal\name\Form\NameFormatForm
 *
 * @group name
 */
class NameFormatFormTest extends UnitTestCase {

  /**
   * @covers ::__construct
   */
  public function testConstructorAssignsFormatParser(): void {
    $parser = $this->createMock(NameFormatParserInterface::class);
    $form = new NameFormatForm($parser);

    $reflection = new \ReflectionProperty(NameFormatForm::class, 'parser');
    $reflection->setAccessible(TRUE);
    $this->assertSame($parser, $reflection->getValue($form));
  }

  /**
   * @covers ::create
   */
  public function testCreateInjectsFormatParser(): void {
    $parser = $this->createMock(NameFormatParserInterface::class);
    $container = new ContainerBuilder();
    $container->set('name.format_parser', $parser);

    $form = NameFormatForm::create($container);
    $this->assertInstanceOf(NameFormatForm::class, $form);

    $reflection = new \ReflectionProperty(NameFormatForm::class, 'parser');
    $reflection->setAccessible(TRUE);
    $this->assertSame($parser, $reflection->getValue($form));
  }

}
