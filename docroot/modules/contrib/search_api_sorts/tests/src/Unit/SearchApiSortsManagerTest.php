<?php

namespace Drupal\Tests\search_api_sorts\Unit;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\search_api\Display\DisplayInterface;
use Drupal\search_api\Display\DisplayPluginManagerInterface;
use Drupal\search_api_sorts\Entity\SearchApiSortsField;
use Drupal\search_api_sorts\SearchApiSortsManager;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the sorts manager.
 *
 * @group search_api_sorts
 *
 * @coversDefaultClass \Drupal\search_api_sorts\SearchApiSortsManager
 */
class SearchApiSortsManagerTest extends UnitTestCase {

  /**
   * ModuleHandler object.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManagerProphecy;

  /**
   * A display object.
   *
   * @var \Drupal\search_api\Display\DisplayInterface
   */
  private $display;

  /**
   * A request object to use for each test case.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  private $request;

  /**
   * A request stack object to store requests.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * The search API Display manager.
   *
   * @var \Drupal\search_api\Display\DisplayPluginManagerInterface
   */
  private $searchApiDisplayManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class)->reveal();

    $this->display = $this->prophesize(DisplayInterface::class)->reveal();

    $this->requestStack = new RequestStack();

    // Only set up the prophecy for now,
    // it will need to be configured differently for each test function.
    $this->entityTypeManagerProphecy = $this->prophesize(EntityTypeManagerInterface::class);

    $this->request = new Request();

    $this->searchApiDisplayManager = $this->prophesize(DisplayPluginManagerInterface::class)->reveal();
  }

  /**
   * Tests getActiveSort.
   *
   * @dataProvider provideSortOrders
   *
   * @covers ::getActiveSort
   */
  public function testGetActiveSort($order_argument, $expected) {
    $this->request->query = new ParameterBag(['sort' => 'sort_field', 'order' => $order_argument]);
    $this->requestStack->push($this->request);

    $manager = $this->entityTypeManagerProphecy->reveal();

    $searchApiSortsManager = new SearchApiSortsManager($this->requestStack, $manager, $this->moduleHandler, $this->searchApiDisplayManager);
    $sorts = $searchApiSortsManager->getActiveSort($this->display);
    $this->assertEquals('sort_field', $sorts->getFieldName());
    $this->assertEquals($expected, $sorts->getOrder());
  }

  /**
   * Tests getEnabledSorts.
   *
   * @covers ::getEnabledSorts
   */
  public function testGetEnabledSorts() {
    $this->requestStack->push($this->request);

    $sortsField = new SearchApiSortsField(['id' => $this->randomMachineName()], 'search_api_sorts_field');

    $storage = $this->getMockBuilder(EntityStorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $storage->expects($this->once())
      ->method('loadByProperties')
      ->willReturn($sortsField);

    try {
      $this->entityTypeManagerProphecy
        ->getStorage('search_api_sorts_field')
        ->willReturn($storage);
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->fail("search_api_sorts storage not found.");
    }

    $manager = $this->entityTypeManagerProphecy->reveal();

    $searchApiSortsManager = new SearchApiSortsManager($this->requestStack, $manager, $this->moduleHandler, $this->searchApiDisplayManager);
    $enabledSorts = $searchApiSortsManager->getEnabledSorts($this->display);

    $this->assertEquals($sortsField, $enabledSorts);
  }

  /**
   * Provides mock data and expected results for ::testActiveSortOrder.
   *
   * @return array
   *   An array of mockable data.
   */
  public function provideSortOrders() {
    return [
      ['asc', 'asc'],
      ['desc', 'desc'],
      ['aaa', 'asc'],
      [NULL, 'asc'],
    ];
  }

}
