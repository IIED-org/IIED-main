<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Form\FormState;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Plugin\views\filter\Fulltext;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the fulltext name Views filter plugin.
 *
 * @group name
 * @coversDefaultClass \Drupal\name\Plugin\views\filter\Fulltext
 */
final class FulltextTest extends UnitTestCase {

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
   * Builds a testable filter plugin.
   */
  private function buildPlugin(Connection $connection): Fulltext {
    return new class([], 'name_fulltext', [], $connection) extends Fulltext {

      /**
       * Whether the plugin should behave as exposed.
       */
      public bool $exposed = FALSE;

      /**
       * {@inheritdoc}
       */
      public function ensureMyTable() {}

      /**
       * Invokes the protected value form builder.
       */
      public function buildValueForm(array &$form, FormState $form_state): void {
        $this->valueForm($form, $form_state);
      }

      /**
       * {@inheritdoc}
       */
      public function isExposed() {
        return $this->exposed;
      }

    };
  }

  /**
   * Sets a protected plugin property value.
   *
   * @param object $plugin
   *   The plugin.
   * @param string $property
   *   Property name.
   * @param mixed $value
   *   Property value.
   */
  private function setProtected(object $plugin, string $property, mixed $value): void {
    $reflection = new \ReflectionObject($plugin);
    while ($reflection instanceof \ReflectionClass) {
      if ($reflection->hasProperty($property)) {
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(TRUE);
        $prop->setValue($plugin, $value);
        return;
      }
      $reflection = $reflection->getParentClass();
    }
  }

  /**
   * Builds a lightweight query spy used by the filter.
   */
  private function createQuerySpy(): object {
    return new class() {

      /**
       * Logged addWhereExpression calls.
       *
       * @var array<int, array{0:mixed,1:mixed,2:mixed}>
       */
      public array $whereExpressionCalls = [];

      /**
       * Logged addWhere calls.
       *
       * @var array<int, array{0:mixed,1:mixed}>
       */
      public array $whereCalls = [];

      /**
       * Placeholder value.
       */
      public function placeholder(): string {
        return ':ph';
      }

      /**
       * Records expression-based conditions.
       */
      public function addWhereExpression(mixed $group, mixed $snippet, mixed $args): void {
        $this->whereExpressionCalls[] = [$group, $snippet, $args];
      }

      /**
       * Records structured conditions.
       */
      public function addWhere(mixed $group, mixed $condition): void {
        $this->whereCalls[] = [$group, $condition];
      }

    };
  }

  /**
   * Returns protected operator metadata.
   */
  private function getOperators(Fulltext $plugin): array {
    $reflection = new \ReflectionObject($plugin);
    $method = $reflection->getMethod('operators');
    $method->setAccessible(TRUE);
    return $method->invoke($plugin);
  }

  /**
   * @covers ::operatorOptions
   */
  public function testOperatorOptionsReturnsAllThreeOperators(): void {
    $connection = $this->createMock(Connection::class);
    $plugin = $this->buildPlugin($connection);

    $options = $plugin->operatorOptions();

    $this->assertArrayHasKey('contains', $options);
    $this->assertArrayHasKey('word', $options);
    $this->assertArrayHasKey('allwords', $options);
  }

  /**
   * @covers ::__construct
   * @covers ::operators
   */
  public function testConstructorStoresConnectionAndOperatorsMetadata(): void {
    $connection = $this->createMock(Connection::class);
    $plugin = $this->buildPlugin($connection);
    $operators = $this->getOperators($plugin);

    $reflection = new \ReflectionObject($plugin);
    $property = $reflection->getProperty('connection');
    $property->setAccessible(TRUE);
    $this->assertSame($connection, $property->getValue($plugin));

    $this->assertSame('op_contains', $operators['contains']['method']);
    $this->assertSame('op_word', $operators['word']['method']);
    $this->assertSame('op_word', $operators['allwords']['method']);
    $this->assertSame(1, $operators['contains']['values']);
    $this->assertSame(1, $operators['word']['values']);
    $this->assertSame(1, $operators['allwords']['values']);
  }

  /**
   * @covers ::create
   */
  public function testCreateInjectsDatabaseConnectionFromContainer(): void {
    $connection = $this->createMock(Connection::class);
    $container = new ContainerBuilder();
    $container->set('database', $connection);

    $plugin = Fulltext::create($container, [], 'name_fulltext', []);

    $this->assertInstanceOf(Fulltext::class, $plugin);

    $reflection = new \ReflectionObject($plugin);
    $property = $reflection->getProperty('connection');
    $property->setAccessible(TRUE);
    $this->assertSame($connection, $property->getValue($plugin));
  }

  /**
   * @covers ::valueForm
   */
  public function testValueFormBuildsExpectedElements(): void {
    $connection = $this->createMock(Connection::class);
    $plugin = $this->buildPlugin($connection);
    $this->setProtected($plugin, 'value', 'Alpha Name');

    $form_state = new FormState();
    $form = [];
    $plugin->buildValueForm($form, $form_state);

    $this->assertSame('textfield', $form['value']['#type']);
    $this->assertSame(15, $form['value']['#size']);
    $this->assertSame('Alpha Name', $form['value']['#default_value']);
    $this->assertSame('Value', (string) $form['value']['#title']);

    $plugin->exposed = TRUE;
    $form = [];
    $plugin->buildValueForm($form, $form_state);
    $this->assertSame('', $form['value']['#title']);
  }

  /**
   * @covers ::query
   */
  public function testQueryDoesNothingWhenValueIsEmpty(): void {
    $connection = $this->createMock(Connection::class);
    $plugin = $this->buildPlugin($connection);

    $query = $this->createQuerySpy();

    $this->setProtected($plugin, 'query', $query);
    $this->setProtected($plugin, 'value', ['']);
    $this->setProtected($plugin, 'operator', 'contains');

    $plugin->query();
    $this->assertCount(0, $query->whereExpressionCalls);
    $this->assertCount(0, $query->whereCalls);
  }

  /**
   * @covers ::query
   */
  public function testQueryBuildsFulltextExpressionAndDispatchesOperator(): void {
    $connection = $this->createMock(Connection::class);
    $plugin = $this->buildPlugin($connection);

    $query = $this->createQuerySpy();
    $this->setProtected($plugin, 'query', $query);
    $this->setProtected($plugin, 'value', ['Pat']);
    $this->setProtected($plugin, 'operator', 'contains');
    $this->setProtected($plugin, 'options', ['group' => 'group_5']);
    $this->setProtected($plugin, 'tableAlias', 'base_alias');
    $this->setProtected($plugin, 'realField', 'name_value');

    $plugin->query();

    $this->assertCount(1, $query->whereExpressionCalls);
    [$group, $snippet] = $query->whereExpressionCalls[0];
    $this->assertSame('group_5', $group);
    $this->assertStringContainsString('LOWER(CONCAT', (string) $snippet);
    $this->assertStringContainsString('base_alias.name_value_title', (string) $snippet);
    $this->assertStringContainsString('base_alias.name_value_given', (string) $snippet);
    $this->assertStringContainsString('base_alias.name_value_middle', (string) $snippet);
    $this->assertStringContainsString('base_alias.name_value_family', (string) $snippet);
    $this->assertStringContainsString('base_alias.name_value_generational', (string) $snippet);
    $this->assertStringContainsString('base_alias.name_value_credentials', (string) $snippet);
  }

  /**
   * @covers ::op_contains
   */
  public function testOpContainsAddsLikeExpressionWithPercents(): void {
    $connection = $this->createMock(Connection::class);
    $connection->expects($this->once())
      ->method('escapeLike')
      ->with('pat_lee')
      ->willReturn('pat\\_lee');
    $plugin = $this->buildPlugin($connection);

    $query = $this->createQuerySpy();

    $this->setProtected($plugin, 'query', $query);
    $this->setProtected($plugin, 'value', ['Pat_Lee']);
    $this->setProtected($plugin, 'options', ['group' => 'group_1']);

    $plugin->op_contains('LOWER(fake_field)');

    $this->assertCount(1, $query->whereExpressionCalls);
    [$group, $snippet, $args] = $query->whereExpressionCalls[0];
    $this->assertSame('group_1', $group);
    $this->assertStringContainsString('LIKE', (string) $snippet);
    $this->assertStringContainsString('% pat\\_lee%', (string) reset($args));
  }

  /**
   * @covers ::op_contains
   */
  public function testOpContainsFallsBackWhenEscapeLikeReturnsNull(): void {
    $connection = $this->createMock(Connection::class);
    $connection->expects($this->once())
      ->method('escapeLike')
      ->with('pat lee')
      ->willReturn(NULL);

    $plugin = $this->buildPlugin($connection);
    $query = $this->createQuerySpy();

    $this->setProtected($plugin, 'query', $query);
    $this->setProtected($plugin, 'value', ['Pat Lee']);
    $this->setProtected($plugin, 'options', ['group' => 'group_6']);

    $plugin->op_contains('LOWER(fake_field)');

    $this->assertCount(1, $query->whereExpressionCalls);
    [$group, $snippet, $args] = $query->whereExpressionCalls[0];
    $this->assertSame('group_6', $group);
    $this->assertStringContainsString('LIKE', (string) $snippet);
    $this->assertStringContainsString('% pat%lee%', (string) reset($args));
  }

  /**
   * @covers ::op_word
   */
  public function testOpWordBuildsOrConditionForWordOperator(): void {
    $condition = $this->createMock(ConditionInterface::class);
    $condition->expects($this->exactly(2))
      ->method('where')
      ->with(
        $this->stringContains('LIKE'),
        $this->callback(static function (array $args): bool {
          $value = (string) reset($args);
          return str_contains($value, 'alpha') || str_contains($value, 'beta');
        }),
      )
      ->willReturnSelf();

    $connection = $this->createMock(Connection::class);
    $connection->expects($this->once())
      ->method('condition')
      ->with('OR')
      ->willReturn($condition);
    $connection->method('escapeLike')
      ->willReturnCallback(static fn(string $word): string => $word);

    $plugin = $this->buildPlugin($connection);

    $query = $this->createQuerySpy();

    $this->setProtected($plugin, 'query', $query);
    $this->setProtected($plugin, 'value', ['alpha beta']);
    $this->setProtected($plugin, 'operator', 'word');
    $this->setProtected($plugin, 'options', ['group' => 'group_2']);

    $plugin->op_word('LOWER(fake_field)');
    $this->assertCount(1, $query->whereCalls);
    $this->assertSame('group_2', $query->whereCalls[0][0]);
    $this->assertSame($condition, $query->whereCalls[0][1]);
  }

  /**
   * @covers ::op_word
   */
  public function testOpWordBuildsAndConditionForAllwordsOperator(): void {
    $condition = $this->createMock(ConditionInterface::class);
    $condition->expects($this->exactly(2))
      ->method('where')
      ->willReturnSelf();

    $connection = $this->createMock(Connection::class);
    $connection->expects($this->once())
      ->method('condition')
      ->with('AND')
      ->willReturn($condition);
    $connection->method('escapeLike')
      ->willReturnCallback(static fn(string $word): string => $word);

    $plugin = $this->buildPlugin($connection);

    $query = $this->createQuerySpy();

    $this->setProtected($plugin, 'query', $query);
    $this->setProtected($plugin, 'value', ['alpha beta']);
    $this->setProtected($plugin, 'operator', 'allwords');
    $this->setProtected($plugin, 'options', ['group' => 'group_3']);

    $plugin->op_word('LOWER(fake_field)');
    $this->assertCount(1, $query->whereCalls);
    $this->assertSame('group_3', $query->whereCalls[0][0]);
    $this->assertSame($condition, $query->whereCalls[0][1]);
  }

  /**
   * @covers ::op_word
   */
  public function testOpWordEscapesSpecialCharacters(): void {
    $condition = $this->createMock(ConditionInterface::class);
    $condition->expects($this->once())
      ->method('where')
      ->with(
        $this->stringContains('LIKE'),
        $this->callback(static function (array $args): bool {
          $value = (string) reset($args);
          return str_contains($value, 'literal\\%\\_value');
        }),
      )
      ->willReturnSelf();

    $connection = $this->createMock(Connection::class);
    $connection->expects($this->once())->method('condition')->with('OR')->willReturn($condition);
    $connection->expects($this->once())
      ->method('escapeLike')
      ->with('literal%_value')
      ->willReturn('literal\\%\\_value');

    $plugin = $this->buildPlugin($connection);

    $query = $this->createQuerySpy();

    $this->setProtected($plugin, 'query', $query);
    $this->setProtected($plugin, 'value', ['literal%_value']);
    $this->setProtected($plugin, 'operator', 'word');
    $this->setProtected($plugin, 'options', ['group' => 'group_4']);

    $plugin->op_word('LOWER(fake_field)');
    $this->assertCount(1, $query->whereCalls);
    $this->assertSame('group_4', $query->whereCalls[0][0]);
    $this->assertSame($condition, $query->whereCalls[0][1]);
  }

}
