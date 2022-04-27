<?php

namespace Drupal\Tests\facets_summary\Unit\Plugin\Processor;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\facets_summary\Plugin\facets_summary\processor\ResetStringProcessor;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Tests\UnitTestCase;

/**
 * Class ResetStringProcessorTest.
 *
 * @group facets
 * @coversDefaultClass \Drupal\facets_summary\Plugin\facets_summary\processor\ResetStringProcessor
 */
class ResetStringProcessorTest extends UnitTestCase {

  /**
   * The processor we're testing.
   *
   * @var \Drupal\facets_summary\Processor\ProcessorInterface|\Drupal\facets_summary\Processor\BuildProcessorInterface
   */
  protected $processor;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $string_translation = $this->prophesize(TranslationInterface::class);

    $container = new ContainerBuilder();
    $container->set('string_translation', $string_translation->reveal());
    $requestStack = $this->getMockBuilder(RequestStack::class)
      ->disableOriginalConstructor()
      ->getMock();
    $container->set('request_stack', $requestStack);
    \Drupal::setContainer($container);
    $this->processor = new ResetStringProcessor([], 'reset_string', [], $requestStack);
  }

  /**
   * Tests the is hidden method.
   *
   * @covers ::isHidden
   */
  public function testIsHidden() {
    $this->assertFalse($this->processor->isHidden());
  }

  /**
   * Tests the is locked method.
   *
   * @covers ::isLocked
   */
  public function testIsLocked() {
    $this->assertFalse($this->processor->isLocked());
  }

  /**
   * Tests the build method.
   *
   * @covers ::build
   */
  public function testBuildWithEmptyItems() {
    // @todo implement
  }

}
