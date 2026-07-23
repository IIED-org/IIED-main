<?php

declare(strict_types=1);

namespace Drupal\Tests\charts\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\charts\Element\ChartDataCollectorTable;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the ChartDataCollectorTable class.
 *
 * @group charts
 *
 * @coversDefaultClass \Drupal\charts\Element\ChartDataCollectorTable
 */
class ChartDataCollectorTableTest extends UnitTestCase {

  /**
   * The ChartDataCollectorTable instance.
   *
   * @var \Drupal\charts\Element\ChartDataCollectorTable
   */
  protected ChartDataCollectorTable $table;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $container = new ContainerBuilder();
    $element_info_manager = $this->prophesize('Drupal\Core\Render\ElementInfoManagerInterface');
    $container->set('plugin.manager.element_info', $element_info_manager->reveal());
    \Drupal::setContainer($container);

    $configuration = [];
    $plugin_id = 'charts_chart';
    $plugin_definition = [];

    $this->table = new ChartDataCollectorTable($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Tests the getInfo() method.
   *
   * @covers ::getInfo
   */
  public function testGetInfo(): void {
    $info = $this->table->getInfo();
    $this->assertIsArray($info);
    $this->assertCount(13, $info);
    $this->assertArrayHasKey('#theme_wrappers', $info);
  }

  /**
   * Tests processDataCollectorTable().
   *
   * @covers ::processDataCollectorTable
   */
  public function testProcessDataCollectorTable(): void {

    $element = [
      '#parents' => ['charts', 'chart'],
      '#default_colors' => [],
      '#import_csv' => TRUE,
      '#initial_columns' => 3,
      '#initial_rows' => 10,
      '#table_drag' => TRUE,
      '#table_wrapper' => '',
      '#value' => [],
    ];
    $form_state = $this->createMock('Drupal\Core\Form\FormStateInterface');
    $form_state->expects($this->exactly(2))
      ->method('getStorage')
      ->willReturn([
        'data_collector_table' => [
          '#parents' => ['charts', 'chart'],
        ],
      ]);

    $complete_form = [];

    $this->table->processDataCollectorTable($element, $form_state, $complete_form);
  }

  /**
   * Tests processDataCollectorTable().
   *
   * @covers ::processDataCollectorTable
   */
  public function testProcessDataCollectorTableEmptyCollector(): void {

    $element = [
      '#parents' => ['charts', 'chart'],
      '#default_colors' => [],
      '#import_csv' => TRUE,
      '#initial_columns' => 3,
      '#initial_rows' => 10,
      '#table_drag' => TRUE,
      '#table_wrapper' => '',
      '#value' => [],
    ];
    $form_state = $this->createMock('Drupal\Core\Form\FormStateInterface');
    $form_state->expects($this->exactly(2))
      ->method('getStorage')
      ->willReturn([
        'charts' => [
          'data_collector_table' => [
            '#parents' => ['charts', 'chart'],
          ],
        ],
        'data_collector_table' => [
          '#parents' => ['charts', 'chart'],
        ],
        'table_categories_identifier' => [
          'chart' => [
            'categories' => [],
          ],
        ],
      ]);

    $complete_form = [];

    $this->table->processDataCollectorTable($element, $form_state, $complete_form);
  }

  /**
   * Tests the swapRows() method.
   *
   * @covers ::swapRows
   */
  public function testSwapRows(): void {
    $table = [
      0 => ['data' => 'row 0', 'weight' => 0],
      1 => ['data' => 'row 1', 'weight' => 1],
    ];

    ChartDataCollectorTable::swapRows($table, 0, 1);

    $this->assertEquals('row 1', $table[0]['data']);
    $this->assertEquals(0, $table[0]['weight']);
    $this->assertEquals('row 0', $table[1]['data']);
    $this->assertEquals(1, $table[1]['weight']);
  }

  /**
   * Tests the swapColumns() method.
   *
   * @covers ::swapColumns
   */
  public function testSwapColumns(): void {
    $table = [
      0 => [0 => 'A', 1 => 'B'],
      1 => [0 => 'C', 1 => 'D'],
    ];

    ChartDataCollectorTable::swapColumns($table, 0, 1);

    $this->assertEquals('B', $table[0][0]);
    $this->assertEquals('A', $table[0][1]);
    $this->assertEquals('D', $table[1][0]);
    $this->assertEquals('C', $table[1][1]);
  }

}
