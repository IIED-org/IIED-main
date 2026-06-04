<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Service;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Service\AutocompleteInterface;
use Drupal\name\Service\AutocompleteService;
use Drupal\name\Service\NameOptionInterface;

/**
 * @coversDefaultClass \Drupal\name\Service\AutocompleteService
 *
 * @group name
 */
class NameAutocompleteServiceTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // The service constructor falls back to \Drupal::entityTypeManager() so
    // that stale containers (cached from pre-upgrade builds that do not yet
    // inject the entity type manager) keep working. Provide a container with
    // a mock entity_type.manager so unit tests exercising that fallback do
    // not blow up with ContainerNotInitializedException.
    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->createMock(EntityTypeManagerInterface::class));
    \Drupal::setContainer($container);
  }

  /**
   * Builds field settings with per-component autocomplete source and separator.
   *
   * @param array<string, list<string>> $sources
   *   Component machine name => list of source identifiers.
   * @param array<string, string> $separators
   *   Component machine name => separator string.
   * @param string $match
   *   Global autocomplete match mode ('starts_with' or 'contains').
   * @param array<string, string> $overrides
   *   Per-component match-mode overrides keyed by component.
   *
   * @return array<string, mixed>
   *   Settings array for FieldDefinitionInterface::getSettings().
   */
  private function fieldSettings(array $sources, array $separators = [], string $match = 'starts_with', array $overrides = []): array {
    $settings = [
      'autocomplete_source' => $sources,
      'autocomplete_separator' => $separators,
      'autocomplete_match' => $match,
      'autocomplete_match_overrides' => $overrides,
    ];
    foreach (['given', 'middle', 'family', 'title', 'credentials', 'generational'] as $component) {
      if (!isset($settings['autocomplete_separator'][$component])) {
        $settings['autocomplete_separator'][$component] = ' ';
      }
      if (!array_key_exists($component, $settings['autocomplete_match_overrides'])) {
        $settings['autocomplete_match_overrides'][$component] = '';
      }
    }
    return $settings;
  }

  /**
   * Creates a test proxy that exposes protected helpers for direct assertions.
   */
  private function createExposedService(NameOptionInterface $provider, ?EntityTypeManagerInterface $entity_type_manager = NULL): AutocompleteService {
    return new class($provider, $entity_type_manager) extends AutocompleteService {

      /**
       * Exposes resolveMatchMode() for unit tests.
       *
       * @param array<string, mixed> $settings
       *   Field settings array.
       * @param string $component
       *   The component machine name.
       */
      public function exposedResolveMatchMode(array $settings, string $component): string {
        return $this->resolveMatchMode($settings, $component);
      }

      /**
       * Exposes stringMatches() for unit tests.
       *
       * @param string $haystack
       *   The string being searched.
       * @param string $needle
       *   The lowercase search string to match.
       * @param string $mode
       *   The match mode.
       */
      public function exposedStringMatches(string $haystack, string $needle, string $mode): bool {
        return $this->stringMatches($haystack, $needle, $mode);
      }

      /**
       * Exposes splitAutocompleteInput() for unit tests.
       *
       * @param string $string
       *   The raw autocomplete input string.
       * @param string $separator
       *   The separator character set.
       *
       * @return array{base: string, test: string}|null
       *   Parsed input parts, or NULL when unusable.
       */
      public function exposedSplitAutocompleteInput(string $string, string $separator): ?array {
        return $this->splitAutocompleteInput($string, $separator);
      }

      /**
       * Exposes appendSeparatorCharacters() for unit tests.
       *
       * @param string $separator
       *   The accumulated separator character set.
       * @param string $component_separator
       *   The separator configured for a single component.
       */
      public function exposedAppendSeparatorCharacters(string $separator, string $component_separator): string {
        return $this->appendSeparatorCharacters($separator, $component_separator);
      }

      /**
       * Exposes collectOptionMatches() for unit tests.
       *
       * @param array<string, mixed> $options
       *   Options to match against.
       * @param string $test_string
       *   Lowercased search token.
       * @param string $mode
       *   Match mode.
       * @param string $base_string
       *   Prefix to prepend to matched keys.
       * @param array<string, string> $matches
       *   Accumulator for matched values.
       * @param int $limit
       *   Remaining match budget (passed by reference).
       */
      public function exposedCollectOptionMatches(array $options, string $test_string, string $mode, string $base_string, array &$matches, int &$limit): void {
        $this->collectOptionMatches($options, $test_string, $mode, $base_string, $matches, $limit);
      }

      /**
       * Exposes collectEntityFieldMatches() for unit tests.
       *
       * @param array<int|string, FieldableEntityInterface> $entities
       *   The entities to inspect.
       * @param string $field_name
       *   The field machine name.
       * @param string $component
       *   The name field component machine name.
       * @param string $needle
       *   The lowercase search term.
       * @param int $limit
       *   The maximum number of unique matches to return.
       * @param string $mode
       *   The match mode.
       *
       * @return array<string, string>
       *   Matching values keyed by value.
       */
      public function exposedCollectEntityFieldMatches(
        array $entities,
        string $field_name,
        string $component,
        string $needle,
        int $limit,
        string $mode,
      ): array {
        return $this->collectEntityFieldMatches(
          $entities,
          $field_name,
          $component,
          $needle,
          $limit,
          $mode,
        );
      }

    };
  }

  /**
   * Creates a mock entity with iterable name field items.
   *
   * @param bool $has_field
   *   Whether the entity has the name field.
   * @param array<int, object> $items
   *   Field item objects to return from the mocked field item list.
   */
  private function createFieldEntity(bool $has_field, array $items = []): FieldableEntityInterface {
    $entity = $this->createMock(FieldableEntityInterface::class);
    $entity->method('hasField')
      ->with('field_author')
      ->willReturn($has_field);

    if (!$has_field) {
      $entity->expects($this->never())->method('get');
      return $entity;
    }

    $field_items = $this->createMockForIntersectionOfInterfaces([
      FieldItemListInterface::class,
      \IteratorAggregate::class,
    ]);
    $field_items->method('getIterator')
      ->willReturn(new \ArrayIterator($items));

    $entity->method('get')
      ->with('field_author')
      ->willReturn($field_items);

    return $entity;
  }

  /**
   * Creates a minimal field item with a component property.
   */
  private function createNameItem(?string $value, string $component = 'given'): \stdClass {
    $item = new \stdClass();
    $item->{$component} = $value;
    return $item;
  }

  /**
   * @covers ::__construct
   */
  public function testConstruct(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service = new AutocompleteService($provider);
    $this->assertInstanceOf(AutocompleteInterface::class, $service);
  }

  /**
   * @covers ::getMatches
   */
  public function testGetMatchesEmptyStringReturnsEmpty(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->never())->method('getOptions');

    $service = new AutocompleteService($provider);
    $this->assertSame([], $service->getMatches($field, 'name', ''));
  }

  /**
   * @covers ::getMatches
   */
  public function testMissingAutocompleteSourceDefaultsToEmptyAndReturnsNoMatches(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn([]);

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->never())->method('getOptions');

    $service = new AutocompleteService($provider);
    $this->assertSame([], $service->getMatches($field, 'name-all', 'x'));
  }

  /**
   * Missing component source keys are normalized to empty source arrays.
   *
   * @covers ::getMatches
   * @covers ::normalizeAutocompleteSettings
   */
  public function testAutocompleteSourceArrayMissingComponentKeysReturnsNoMatches(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn([
      'autocomplete_source' => [],
    ]);

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->never())->method('getOptions');

    $service = new class($provider) extends AutocompleteService {
      /**
       * Recorded arguments from each findFieldValues invocation.
       *
       * @var array<int, string>
       */
      public array $lookupComponents = [];

      /**
       * {@inheritdoc}
       */
      public function findFieldValues(FieldDefinitionInterface $field, string $component, string $term, int $limit, string $mode = 'starts_with'): array {
        $this->lookupComponents[] = $component;
        return ['leak' => 'leak'];
      }

    };

    $this->assertSame([], $service->getMatches($field, 'name-all', 'Al'));
    $this->assertSame([], $service->lookupComponents);
  }

  /**
   * The name-all target expands to every autocomplete component.
   *
   * @covers ::getMatches
   * @covers ::resolveTargetComponents
   */
  public function testNameAllTargetDelegatesAllDataComponents(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings([
      'given' => ['data'],
      'middle' => ['data'],
      'family' => ['data'],
      'title' => ['data'],
      'credentials' => ['data'],
      'generational' => ['data'],
    ]));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->never())->method('getOptions');

    $service = new class($provider) extends AutocompleteService {
      /**
       * Recorded arguments from each findFieldValues invocation.
       *
       * @var array<int, string>
       */
      public array $lookupComponents = [];

      /**
       * {@inheritdoc}
       */
      public function findFieldValues(FieldDefinitionInterface $field, string $component, string $term, int $limit, string $mode = 'starts_with'): array {
        $this->lookupComponents[] = $component;
        return [];
      }

    };

    $this->assertSame([], $service->getMatches($field, 'name-all', 'Al'));
    $this->assertSame(
      ['given', 'middle', 'family', 'title', 'credentials', 'generational'],
      $service->lookupComponents,
    );
  }

  /**
   * @covers ::getMatches
   */
  public function testNameAllTargetWithAllComponentSourcesQueriesTitleOptions(): void {
    $sources = [
      'given' => ['data'],
      'middle' => ['data'],
      'family' => ['data'],
      'title' => ['title'],
      'credentials' => ['data'],
      'generational' => ['data'],
    ];
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings($sources));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->once())
      ->method('getOptions')
      ->with($field, 'title')
      ->willReturn([
        'Mr.' => 'Mr.',
        'Mrs.' => 'Mrs.',
      ]);

    $service = new AutocompleteService($provider);
    $matches = $service->getMatches($field, 'name-all', 'M');
    $this->assertSame([
      'Mr.' => 'Mr.',
      'Mrs.' => 'Mrs.',
    ], $matches);
  }

  /**
   * @covers ::getMatches
   */
  public function testSingleComponentTitleReturnsMatches(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings([
      'title' => ['title'],
    ]));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->once())
      ->method('getOptions')
      ->with($field, 'title')
      ->willReturn(['Ms.' => 'Ms.']);

    $service = new AutocompleteService($provider);
    $this->assertSame(['Ms.' => 'Ms.'], $service->getMatches($field, 'title', 'Ms'));
  }

  /**
   * @covers ::getMatches
   */
  public function testEmptyAutocompleteSeparatorTreatedAsSpace(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings(
      ['title' => ['title']],
      ['title' => ''],
    ));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->once())
      ->method('getOptions')
      ->with($field, 'title')
      ->willReturn(['Dr.' => 'Dr.']);

    $service = new AutocompleteService($provider);
    $this->assertSame(
      ['Dr.' => 'Dr.'],
      $service->getMatches($field, 'title', 'Dr'),
    );
  }

  /**
   * @covers ::getMatches
   */
  public function testComponentRemovedWhenNoApplicableSourceAfterFiltering(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings([
      'given' => ['title'],
    ]));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->never())->method('getOptions');

    $service = new AutocompleteService($provider);
    $this->assertSame(
      [],
      $service->getMatches($field, 'given', 'anything'),
    );
  }

  /**
   * @covers ::getMatches
   */
  public function testSingleComponentGenerationalReturnsMatches(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings([
      'generational' => ['generational'],
    ]));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->once())
      ->method('getOptions')
      ->with($field, 'generational')
      ->willReturn(['Jr.' => 'Jr.']);

    $service = new AutocompleteService($provider);
    $this->assertSame(['Jr.' => 'Jr.'], $service->getMatches($field, 'generational', 'Jr'));
  }

  /**
   * Data provider: single-component targets that use only data source.
   *
   * @return \Generator<string, array{string}>
   *   Yields component machine name => single-element argument list.
   */
  public static function dataOnlyComponentTargetProvider(): \Generator {
    yield 'given' => ['given'];
    yield 'middle' => ['middle'];
    yield 'family' => ['family'];
    yield 'credentials' => ['credentials'];
  }

  /**
   * @covers ::getMatches
   *
   * @dataProvider dataOnlyComponentTargetProvider
   */
  public function testSingleComponentDataSourceDoesNotCallOptionsProvider(string $target): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings([
      $target => ['data'],
    ]));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->never())->method('getOptions');

    $service = new AutocompleteService($provider);
    $this->assertSame([], $service->getMatches($field, $target, 'anything'));
  }

  /**
   * @covers ::getMatches
   */
  public function testDefaultTargetParsesHyphenComponentsAndIgnoresUnknownTokens(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings([
      'given' => ['data'],
      'family' => ['data'],
    ]));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->never())->method('getOptions');

    $service = new AutocompleteService($provider);
    $this->assertSame(
      [],
      $service->getMatches($field, 'given-family-unknown', 'test'),
    );
  }

  /**
   * Composite targets with no known components do not perform lookups.
   *
   * @covers ::getMatches
   * @covers ::resolveCompositeTargetComponents
   * @covers ::resolveTargetComponents
   */
  public function testCompositeTargetWithOnlyUnknownTokensDoesNotPerformLookups(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings([
      'given' => ['data'],
      'family' => ['data'],
    ]));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->never())->method('getOptions');

    $service = new class($provider) extends AutocompleteService {
      /**
       * Recorded arguments from each findFieldValues invocation.
       *
       * @var array<int, string>
       */
      public array $lookupComponents = [];

      /**
       * {@inheritdoc}
       */
      public function findFieldValues(FieldDefinitionInterface $field, string $component, string $term, int $limit, string $mode = 'starts_with'): array {
        $this->lookupComponents[] = $component;
        return ['leak' => 'leak'];
      }

    };

    $this->assertSame([], $service->getMatches($field, 'bogus-tokens', 'Al'));
    $this->assertSame([], $service->lookupComponents);
  }

  /**
   * Composite targets retain known components and ignore unknown tokens.
   *
   * @covers ::getMatches
   * @covers ::resolveCompositeTargetComponents
   * @covers ::resolveTargetComponents
   */
  public function testCompositeTargetWithKnownAndUnknownTokensUsesKnownComponents(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings([
      'given' => ['data'],
      'family' => ['data'],
    ]));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->never())->method('getOptions');

    $service = new class($provider) extends AutocompleteService {
      /**
       * Recorded arguments from each findFieldValues invocation.
       *
       * @var array<int, string>
       */
      public array $lookupComponents = [];

      /**
       * {@inheritdoc}
       */
      public function findFieldValues(FieldDefinitionInterface $field, string $component, string $term, int $limit, string $mode = 'starts_with'): array {
        $this->lookupComponents[] = $component;
        return [];
      }

    };

    $this->assertSame([], $service->getMatches($field, 'given-unknown-family', 'Al'));
    $this->assertSame(['given', 'family'], $service->lookupComponents);
  }

  /**
   * @covers ::getMatches
   */
  public function testDefaultTargetWithTitleComponentReturnsTitleMatches(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings([
      'title' => ['title'],
      'given' => ['data'],
    ]));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->once())
      ->method('getOptions')
      ->with($field, 'title')
      ->willReturn(['Dr.' => 'Dr.']);

    $service = new AutocompleteService($provider);
    $this->assertSame(
      ['Dr.' => 'Dr.'],
      $service->getMatches($field, 'title-given', 'Dr'),
    );
  }

  /**
   * @covers ::mapAssoc
   */
  public function testMapAssoc(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service = new AutocompleteService($provider);
    $this->assertSame(
      ['a' => 'a', 'b' => 'b'],
      $service->mapAssoc(['a', 'b']),
    );
  }

  /**
   * Tests that the 'data' source branch isolates to the requested component.
   *
   * Exercises the 'data' source branch for a single-component target and
   * confirms per-component isolation (given-name request only queries given).
   *
   * @covers ::getMatches
   */
  public function testDataSourceDelegatesPerComponentAndIsolatesOtherComponents(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings([
      'given' => ['data'],
      'family' => ['data'],
    ]));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->never())->method('getOptions');

    $service = new class($provider) extends AutocompleteService {
      /**
       * Recorded arguments from each findFieldValues invocation.
       *
       * @var array<int, array{0: string, 1: string, 2: int}>
       */
      public array $lookupCalls = [];

      /**
       * {@inheritdoc}
       */
      public function findFieldValues(FieldDefinitionInterface $field, string $component, string $term, int $limit, string $mode = "starts_with"): array {
        $this->lookupCalls[] = [$component, $term, $limit];
        if ($component === 'given') {
          return ['Alice' => 'Alice', 'Alfred' => 'Alfred'];
        }
        return ['Smith' => 'Smith'];
      }

    };

    $matches = $service->getMatches($field, 'given', 'Al');
    $this->assertSame(['Alice' => 'Alice', 'Alfred' => 'Alfred'], $matches);
    $this->assertCount(1, $service->lookupCalls);
    $this->assertSame('given', $service->lookupCalls[0][0]);
    $this->assertSame('al', $service->lookupCalls[0][1]);
  }

  /**
   * Tests that the family endpoint never queries the given-name component.
   *
   * Family-name endpoint must never delegate a 'given' component lookup, even
   * when 'given' is also configured with the data source.
   *
   * @covers ::getMatches
   */
  public function testDataSourceFamilyEndpointNeverQueriesGivenComponent(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings([
      'given' => ['data'],
      'family' => ['data'],
    ]));

    $provider = $this->createMock(NameOptionInterface::class);

    $service = new class($provider) extends AutocompleteService {
      /**
       * Recorded arguments from each findFieldValues invocation.
       *
       * @var array<int, array{0: string, 1: string, 2: int}>
       */
      public array $lookupCalls = [];

      /**
       * {@inheritdoc}
       */
      public function findFieldValues(FieldDefinitionInterface $field, string $component, string $term, int $limit, string $mode = "starts_with"): array {
        $this->lookupCalls[] = [$component, $term, $limit];
        return ['Smith' => 'Smith'];
      }

    };

    $matches = $service->getMatches($field, 'family', 'Sm');
    $this->assertSame(['Smith' => 'Smith'], $matches);
    $this->assertCount(1, $service->lookupCalls);
    $this->assertSame('family', $service->lookupCalls[0][0]);
    $this->assertNotContains('given', array_column($service->lookupCalls, 0));
  }

  /**
   * @covers ::getMatches
   */
  public function testDataSourceNotQueriedWhenSourceListIsEmpty(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings([
      'given' => [],
    ]));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->never())->method('getOptions');

    $service = new class($provider) extends AutocompleteService {
      /**
       * Recorded arguments from each findFieldValues invocation.
       *
       * @var array<int, array{0: string, 1: string, 2: int}>
       */
      public array $lookupCalls = [];

      /**
       * {@inheritdoc}
       */
      public function findFieldValues(FieldDefinitionInterface $field, string $component, string $term, int $limit, string $mode = "starts_with"): array {
        $this->lookupCalls[] = [$component, $term, $limit];
        return ['leak' => 'leak'];
      }

    };

    $matches = $service->getMatches($field, 'given', 'Al');
    $this->assertSame([], $matches);
    $this->assertSame([], $service->lookupCalls);
  }

  /**
   * Empty autocomplete source arrays remove the component from the plan.
   *
   * @covers ::buildAutocompletePlan
   * @covers ::getMatches
   */
  public function testEmptyAutocompleteSourceRemovesComponentFromPlan(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings([
      'given' => [],
    ]));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->never())->method('getOptions');

    $service = new class($provider) extends AutocompleteService {
      /**
       * Recorded arguments from each findFieldValues invocation.
       *
       * @var array<int, string>
       */
      public array $lookupComponents = [];

      /**
       * {@inheritdoc}
       */
      public function findFieldValues(FieldDefinitionInterface $field, string $component, string $term, int $limit, string $mode = 'starts_with'): array {
        $this->lookupComponents[] = $component;
        return ['leak' => 'leak'];
      }

    };

    $this->assertSame([], $service->getMatches($field, 'given', 'Al'));
    $this->assertSame([], $service->lookupComponents);
  }

  /**
   * Unknown source keys remove the component after source filtering.
   *
   * @covers ::buildAutocompletePlan
   * @covers ::getMatches
   */
  public function testUnknownAutocompleteSourceRemovesComponentFromPlan(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings([
      'given' => ['custom_source'],
    ]));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->never())->method('getOptions');

    $service = new class($provider) extends AutocompleteService {
      /**
       * Recorded arguments from each findFieldValues invocation.
       *
       * @var array<int, string>
       */
      public array $lookupComponents = [];

      /**
       * {@inheritdoc}
       */
      public function findFieldValues(FieldDefinitionInterface $field, string $component, string $term, int $limit, string $mode = 'starts_with'): array {
        $this->lookupComponents[] = $component;
        return ['leak' => 'leak'];
      }

    };

    $this->assertSame([], $service->getMatches($field, 'given', 'Al'));
    $this->assertSame([], $service->lookupComponents);
  }

  /**
   * The "name" target must query given, middle, and family in order.
   *
   * @covers ::getMatches
   */
  public function testNameTargetQueriesGivenMiddleFamilyInOrder(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings([
      'given' => ['data'],
      'middle' => ['data'],
      'family' => ['data'],
    ]));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->never())->method('getOptions');

    $service = new class($provider) extends AutocompleteService {
      /**
       * Recorded arguments from each findFieldValues invocation.
       *
       * @var array<int, array{0: string, 1: string, 2: int}>
       */
      public array $lookupCalls = [];

      /**
       * {@inheritdoc}
       */
      public function findFieldValues(FieldDefinitionInterface $field, string $component, string $term, int $limit, string $mode = 'starts_with'): array {
        $this->lookupCalls[] = [$component, $term, $limit];
        return match ($component) {
          'given' => ['Sam' => 'Sam'],
          'middle' => ['Sage' => 'Sage'],
          'family' => ['Sanders' => 'Sanders'],
          default => [],
        };
      }

    };

    $matches = $service->getMatches($field, 'name', 'Sa');
    $this->assertSame([
      'Sam' => 'Sam',
      'Sage' => 'Sage',
      'Sanders' => 'Sanders',
    ], $matches);
    $this->assertSame(['given', 'middle', 'family'], array_column($service->lookupCalls, 0));
    $this->assertSame([10, 9, 8], array_column($service->lookupCalls, 2));
    $this->assertSame(['sa', 'sa', 'sa'], array_column($service->lookupCalls, 1));
  }

  /**
   * Composite targets preserve source order and base-string prefixing.
   *
   * @covers ::getMatches
   */
  public function testCompositeTargetPreservesOrderingAndBasePrefix(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings(
      [
        'given' => ['data'],
        'family' => ['data'],
      ],
      [
        'given' => '/',
        'family' => '/',
      ],
    ));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->never())->method('getOptions');

    $service = new class($provider) extends AutocompleteService {
      /**
       * Recorded arguments from each findFieldValues invocation.
       *
       * @var array<int, array{0: string, 1: string, 2: int}>
       */
      public array $lookupCalls = [];

      /**
       * {@inheritdoc}
       */
      public function findFieldValues(FieldDefinitionInterface $field, string $component, string $term, int $limit, string $mode = 'starts_with'): array {
        $this->lookupCalls[] = [$component, $term, $limit];
        return $component === 'family'
          ? ['Smith' => 'Smith']
          : [];
      }

    };

    $matches = $service->getMatches($field, 'given-family', 'Alex/Sm');
    $this->assertSame(['Alex/Smith' => 'Smith'], $matches);
    $this->assertSame(['given', 'family'], array_column($service->lookupCalls, 0));
    $this->assertSame(['sm', 'sm'], array_column($service->lookupCalls, 1));
  }

  /**
   * Tests that title-options and field-data sources both populate results.
   *
   * When the title component enables both 'title' options and 'data', both
   * are consulted and results are capped under the shared per-request limit.
   *
   * @covers ::getMatches
   */
  public function testTitleSourceAndDataSourceBothConsultedAndLimited(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings([
      'title' => ['title', 'data'],
    ]));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->once())
      ->method('getOptions')
      ->with($field, 'title')
      ->willReturn(['Mx.' => 'Mx.']);

    $service = new class($provider) extends AutocompleteService {
      /**
       * Recorded arguments from each findFieldValues invocation.
       *
       * @var array<int, array{0: string, 1: string, 2: int}>
       */
      public array $lookupCalls = [];

      /**
       * {@inheritdoc}
       */
      public function findFieldValues(FieldDefinitionInterface $field, string $component, string $term, int $limit, string $mode = "starts_with"): array {
        $this->lookupCalls[] = [$component, $term, $limit];
        return ['Master' => 'Master'];
      }

    };

    $matches = $service->getMatches($field, 'title', 'M');
    $this->assertSame(['Mx.' => 'Mx.', 'Master' => 'Master'], $matches);
    $this->assertCount(1, $service->lookupCalls);
    $this->assertSame('title', $service->lookupCalls[0][0]);
  }

  /**
   * Generational options honor contains mode for substring matches.
   *
   * @covers ::getMatches
   */
  public function testGetMatchesGenerationalContainsMatchesSubstring(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings(
      ['generational' => ['generational']],
      [],
      'contains',
    ));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->method('getOptions')->willReturn(['Junior' => 'Junior']);

    $service = new AutocompleteService($provider);
    $matches = $service->getMatches($field, 'generational', 'nio');
    $this->assertSame(['Junior' => 'Junior'], $matches);
  }

  /**
   * Generational options reject substring matches under starts_with mode.
   *
   * @covers ::getMatches
   */
  public function testGetMatchesGenerationalStartsWithRejectsSubstring(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings(
      ['generational' => ['generational']],
      [],
      'starts_with',
    ));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->method('getOptions')->willReturn(['Junior' => 'Junior']);

    $service = new AutocompleteService($provider);
    $this->assertSame([], $service->getMatches($field, 'generational', 'nio'));
  }

  /**
   * Unknown targets must not perform option or field-data lookups.
   *
   * @covers ::getMatches
   */
  public function testUnknownTargetDoesNotPerformLookups(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings([
      'given' => ['data'],
      'title' => ['title'],
    ]));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->expects($this->never())->method('getOptions');

    $service = new class($provider) extends AutocompleteService {
      /**
       * Recorded arguments from each findFieldValues invocation.
       *
       * @var array<int, array{0: string, 1: string, 2: int}>
       */
      public array $lookupCalls = [];

      /**
       * {@inheritdoc}
       */
      public function findFieldValues(FieldDefinitionInterface $field, string $component, string $term, int $limit, string $mode = 'starts_with'): array {
        $this->lookupCalls[] = [$component, $term, $limit];
        return ['leak' => 'leak'];
      }

    };

    $this->assertSame([], $service->getMatches($field, 'bogus-target', 'Al'));
    $this->assertSame([], $service->lookupCalls);
  }

  /**
   * @covers ::findFieldValues
   */
  public function testFindFieldValuesShortCircuitsWhenEntityTypeManagerMissing(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service = new AutocompleteService($provider);

    // Force the entity type manager property back to NULL so we can exercise
    // the short-circuit branch. The constructor always resolves it via the
    // container fallback, which is intentional for stale-container upgrades.
    $reflection = new \ReflectionProperty($service, 'entityTypeManager');
    $reflection->setValue($service, NULL);

    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->expects($this->never())->method('getTargetEntityTypeId');

    $this->assertSame([], $service->findFieldValues($field, 'given', 'Al', 5));
  }

  /**
   * @covers ::findFieldValues
   */
  public function testFindFieldValuesEmptyTermReturnsEmpty(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service = new AutocompleteService($provider);

    $field = $this->createMock(FieldDefinitionInterface::class);
    $this->assertSame([], $service->findFieldValues($field, 'given', '', 5));
  }

  /**
   * @covers ::findFieldValues
   */
  public function testFindFieldValuesRejectsUnknownComponent(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service = new AutocompleteService($provider);

    $field = $this->createMock(FieldDefinitionInterface::class);
    $this->assertSame([], $service->findFieldValues($field, 'bogus', 'Al', 5));
  }

  /**
   * Queries must be issued with accessCheck(TRUE) and per-component conditions.
   *
   * @covers ::findFieldValues
   */
  public function testFindFieldValuesQueriesPerComponentWithAccessCheck(): void {
    $query = $this->getMockBuilder(QueryInterface::class)->getMock();
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(TRUE)
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('condition')
      ->with('field_author.given', 'al', 'STARTS_WITH')
      ->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $etm = $this->createMock(EntityTypeManagerInterface::class);
    $etm->method('getStorage')->with('node')->willReturn($storage);

    $provider = $this->createMock(NameOptionInterface::class);
    $service = new AutocompleteService($provider, $etm);

    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getTargetEntityTypeId')->willReturn('node');
    $field->method('getName')->willReturn('field_author');

    $this->assertSame([], $service->findFieldValues($field, 'given', 'al', 5));
  }

  /**
   * Contains mode must issue a CONTAINS query, not STARTS_WITH.
   *
   * @covers ::findFieldValues
   */
  public function testFindFieldValuesContainsModeIssuesContainsQuery(): void {
    $query = $this->getMockBuilder(QueryInterface::class)->getMock();
    $query->expects($this->once())->method('accessCheck')->with(TRUE)->willReturnSelf();
    $query->expects($this->once())
      ->method('condition')
      ->with('field_author.given', 'li', 'CONTAINS')
      ->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $etm = $this->createMock(EntityTypeManagerInterface::class);
    $etm->method('getStorage')->with('node')->willReturn($storage);

    $provider = $this->createMock(NameOptionInterface::class);
    $service = new AutocompleteService($provider, $etm);

    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getTargetEntityTypeId')->willReturn('node');
    $field->method('getName')->willReturn('field_author');

    $this->assertSame([], $service->findFieldValues($field, 'given', 'li', 5, 'contains'));
  }

  /**
   * Global contains flows through to findFieldValues as mode 'contains'.
   *
   * @covers ::getMatches
   */
  public function testGetMatchesForwardsGlobalContainsModeToLookup(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings(
      ['given' => ['data']],
      [],
      'contains',
    ));

    $provider = $this->createMock(NameOptionInterface::class);

    $service = new class($provider) extends AutocompleteService {
      /**
       * Recorded arguments from each findFieldValues invocation.
       *
       * @var array<int, array{0: string, 1: string, 2: int, 3: string}>
       */
      public array $lookupCalls = [];

      /**
       * {@inheritdoc}
       */
      public function findFieldValues(FieldDefinitionInterface $field, string $component, string $term, int $limit, string $mode = 'starts_with'): array {
        $this->lookupCalls[] = [$component, $term, $limit, $mode];
        return ['Alice' => 'Alice'];
      }

    };

    $service->getMatches($field, 'given', 'li');
    $this->assertCount(1, $service->lookupCalls);
    $this->assertSame('contains', $service->lookupCalls[0][3]);
  }

  /**
   * Per-component override beats the global default in both directions.
   *
   * @covers ::getMatches
   */
  public function testGetMatchesOverrideBeatsGlobalDefault(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings(
      ['given' => ['data'], 'family' => ['data']],
      [],
      'contains',
      ['given' => 'starts_with'],
    ));

    $provider = $this->createMock(NameOptionInterface::class);

    $service = new class($provider) extends AutocompleteService {
      /**
       * Recorded arguments from each findFieldValues invocation.
       *
       * @var array<int, array{0: string, 1: string, 2: int, 3: string}>
       */
      public array $lookupCalls = [];

      /**
       * {@inheritdoc}
       */
      public function findFieldValues(FieldDefinitionInterface $field, string $component, string $term, int $limit, string $mode = 'starts_with'): array {
        $this->lookupCalls[] = [$component, $term, $limit, $mode];
        return [];
      }

    };

    $service->getMatches($field, 'given', 'al');
    $service->getMatches($field, 'family', 'al');

    $this->assertCount(2, $service->lookupCalls);
    $byComponent = [];
    foreach ($service->lookupCalls as $call) {
      $byComponent[$call[0]] = $call[3];
    }
    $this->assertSame('starts_with', $byComponent['given']);
    $this->assertSame('contains', $byComponent['family']);
  }

  /**
   * Title in-memory matching honors contains mode when globally configured.
   *
   * @covers ::getMatches
   */
  public function testGetMatchesTitleContainsMatchesSubstring(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings(
      ['title' => ['title']],
      [],
      'contains',
    ));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->method('getOptions')->willReturn(['Doctor' => 'Doctor']);

    $service = new AutocompleteService($provider);
    $matches = $service->getMatches($field, 'title', 'oct');
    $this->assertSame(['Doctor' => 'Doctor'], $matches);
  }

  /**
   * Title in-memory matching rejects substring under starts_with.
   *
   * @covers ::getMatches
   */
  public function testGetMatchesTitleStartsWithRejectsSubstring(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getSettings')->willReturn($this->fieldSettings(
      ['title' => ['title']],
      [],
      'starts_with',
    ));

    $provider = $this->createMock(NameOptionInterface::class);
    $provider->method('getOptions')->willReturn(['Doctor' => 'Doctor']);

    $service = new AutocompleteService($provider);
    $this->assertSame([], $service->getMatches($field, 'title', 'oct'));
  }

  /**
   * Empty or invalid overrides fall back to the global contains setting.
   *
   * @covers ::resolveMatchMode
   */
  public function testResolveMatchModeFallsBackToGlobalContains(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service = $this->createExposedService($provider);

    $settings = $this->fieldSettings(
      ['given' => ['data']],
      [],
      'contains',
      ['given' => 'bogus'],
    );

    $this->assertSame('contains', $service->exposedResolveMatchMode($settings, 'given'));
  }

  /**
   * Explicit valid overrides must win over the global default.
   *
   * @covers ::resolveMatchMode
   */
  public function testResolveMatchModePrefersValidOverride(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service = $this->createExposedService($provider);

    $contains_settings = $this->fieldSettings(
      ['given' => ['data']],
      [],
      'starts_with',
      ['given' => 'contains'],
    );
    $starts_with_settings = $this->fieldSettings(
      ['given' => ['data']],
      [],
      'contains',
      ['given' => 'starts_with'],
    );

    $this->assertSame('contains', $service->exposedResolveMatchMode($contains_settings, 'given'));
    $this->assertSame('starts_with', $service->exposedResolveMatchMode($starts_with_settings, 'given'));
  }

  /**
   * Empty needles are rejected before any strpos work is attempted.
   *
   * @covers ::stringMatches
   */
  public function testStringMatchesRejectsEmptyNeedle(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service = $this->createExposedService($provider);

    $this->assertFalse($service->exposedStringMatches('Alice', '', 'contains'));
  }

  /**
   * Missing entity type ids short-circuit field-data lookups.
   *
   * @covers ::findFieldValues
   */
  public function testFindFieldValuesReturnsEmptyWhenEntityTypeIdMissing(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service = new AutocompleteService($provider);

    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getTargetEntityTypeId')->willReturn(NULL);
    $field->method('getName')->willReturn('field_author');

    $this->assertSame([], $service->findFieldValues($field, 'given', 'Al', 5));
  }

  /**
   * Missing field names short-circuit field-data lookups.
   *
   * @covers ::findFieldValues
   */
  public function testFindFieldValuesReturnsEmptyWhenFieldNameMissing(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service = new AutocompleteService($provider);

    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getTargetEntityTypeId')->willReturn('node');
    $field->method('getName')->willReturn('');

    $this->assertSame([], $service->findFieldValues($field, 'given', 'Al', 5));
  }

  /**
   * Storage lookup failures are swallowed and treated as no matches.
   *
   * @covers ::findFieldValues
   */
  public function testFindFieldValuesReturnsEmptyWhenStorageLookupThrows(): void {
    $storage_exception = new \Exception('Lookup failed.');
    $etm = $this->createMock(EntityTypeManagerInterface::class);
    $etm->method('getStorage')
      ->with('node')
      ->willThrowException($storage_exception);

    $provider = $this->createMock(NameOptionInterface::class);
    $service = new AutocompleteService($provider, $etm);

    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getTargetEntityTypeId')->willReturn('node');
    $field->method('getName')->willReturn('field_author');

    $this->assertSame([], $service->findFieldValues($field, 'given', 'Al', 5));
  }

  /**
   * SplitAutocompleteInput returns NULL when input ends with a separator.
   *
   * When the raw string ends with the separator character, preg_split yields
   * an empty trailing segment; array_pop returns '' and the method returns NULL
   * (line 250 in AutocompleteService).
   *
   * @covers ::splitAutocompleteInput
   */
  public function testSplitAutocompleteInputReturnsNullWhenTestStringIsEmpty(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service  = $this->createExposedService($provider);

    // 'Alice ' ends with a space separator: preg_split gives ['Alice', ''],
    // array_pop returns '', which is empty and returns NULL.
    $this->assertNull($service->exposedSplitAutocompleteInput('Alice ', ' '));
  }

  /**
   * Empty component separators are normalized to a single space.
   *
   * @covers ::appendSeparatorCharacters
   */
  public function testAppendSeparatorCharactersDefaultsEmptySeparatorToSpace(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service  = $this->createExposedService($provider);

    $this->assertSame(' ', $service->exposedAppendSeparatorCharacters('', ''));
  }

  /**
   * Empty accumulated separators make autocomplete input unusable.
   *
   * @covers ::splitAutocompleteInput
   */
  public function testSplitAutocompleteInputReturnsNullWhenSeparatorIsEmpty(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service  = $this->createExposedService($provider);

    $this->assertNull($service->exposedSplitAutocompleteInput('Alice', ''));
  }

  /**
   * SplitAutocompleteInput returns NULL when preg_split signals failure.
   *
   * Exercises the guard at line 249 that handles preg_split returning FALSE
   * on a PCRE-level error. The protected pregSplitPieces() helper is
   * overridden to inject a FALSE return so the branch is reachable without
   * triggering a real PCRE failure in the test environment.
   *
   * @covers ::splitAutocompleteInput
   * @covers ::pregSplitPieces
   */
  public function testSplitAutocompleteInputReturnsNullWhenPregSplitFails(): void {
    $provider = $this->createMock(NameOptionInterface::class);

    $service = new class($provider) extends AutocompleteService {

      /**
       * Simulates a PCRE failure by returning FALSE instead of an array.
       *
       * {@inheritdoc}
       */
      protected function pregSplitPieces(string $pattern, string $subject): array|false {
        return FALSE;
      }

      /**
       * Exposes splitAutocompleteInput() for direct assertion.
       *
       * @param string $string
       *   The raw autocomplete input string.
       * @param string $separator
       *   The separator character set.
       *
       * @return array{base: string, test: string}|null
       *   Parsed input parts, or NULL.
       */
      public function exposedSplitAutocompleteInput(string $string, string $separator): ?array {
        return $this->splitAutocompleteInput($string, $separator);
      }

    };

    $this->assertNull($service->exposedSplitAutocompleteInput('Alice', ' '));
  }

  /**
   * CollectOptionMatches stops and returns early when the limit reaches zero.
   *
   * Exercises the early-return guard at line 277 in AutocompleteService.
   *
   * @covers ::collectOptionMatches
   */
  public function testCollectOptionMatchesReturnsEarlyWhenLimitExhausted(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service  = $this->createExposedService($provider);

    $options = [];
    for ($i = 1; $i <= 12; $i++) {
      $options["match{$i}"] = "match{$i}";
    }

    $matches = [];
    $limit   = 2;
    $service->exposedCollectOptionMatches(
      $options,
      'match',
      'starts_with',
      '',
      $matches,
      $limit,
    );

    $this->assertCount(2, $matches);
    $this->assertSame(0, $limit);
  }

  /**
   * Entities without the requested field do not contribute matches.
   *
   * @covers ::collectEntityFieldMatches
   */
  public function testCollectEntityFieldMatchesSkipsEntitiesWithoutField(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service  = $this->createExposedService($provider);
    $entity   = $this->createFieldEntity(FALSE);

    $matches = $service->exposedCollectEntityFieldMatches(
      [$entity],
      'field_author',
      'given',
      'al',
      10,
      'starts_with',
    );

    $this->assertSame([], $matches);
  }

  /**
   * Field item NULL values are ignored.
   *
   * @covers ::collectEntityFieldMatches
   */
  public function testCollectEntityFieldMatchesSkipsNullValues(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service  = $this->createExposedService($provider);
    $entity   = $this->createFieldEntity(TRUE, [
      $this->createNameItem(NULL),
    ]);

    $matches = $service->exposedCollectEntityFieldMatches(
      [$entity],
      'field_author',
      'given',
      'al',
      10,
      'starts_with',
    );

    $this->assertSame([], $matches);
  }

  /**
   * Field item empty string values are ignored.
   *
   * @covers ::collectEntityFieldMatches
   */
  public function testCollectEntityFieldMatchesSkipsEmptyStringValues(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service  = $this->createExposedService($provider);
    $entity   = $this->createFieldEntity(TRUE, [
      $this->createNameItem(''),
    ]);

    $matches = $service->exposedCollectEntityFieldMatches(
      [$entity],
      'field_author',
      'given',
      'al',
      10,
      'starts_with',
    );

    $this->assertSame([], $matches);
  }

  /**
   * Starts-with matching is case-insensitive.
   *
   * @covers ::collectEntityFieldMatches
   */
  public function testCollectEntityFieldMatchesCaseInsensitiveStartsWith(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service  = $this->createExposedService($provider);
    $entity   = $this->createFieldEntity(TRUE, [
      $this->createNameItem('Alice'),
    ]);

    $matches = $service->exposedCollectEntityFieldMatches(
      [$entity],
      'field_author',
      'given',
      'al',
      10,
      'starts_with',
    );

    $this->assertSame(['Alice' => 'Alice'], $matches);
  }

  /**
   * Contains mode allows mid-string matches.
   *
   * @covers ::collectEntityFieldMatches
   */
  public function testCollectEntityFieldMatchesContainsMidString(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service  = $this->createExposedService($provider);
    $entity   = $this->createFieldEntity(TRUE, [
      $this->createNameItem('Alice'),
    ]);

    $matches = $service->exposedCollectEntityFieldMatches(
      [$entity],
      'field_author',
      'given',
      'lic',
      10,
      'contains',
    );

    $this->assertSame(['Alice' => 'Alice'], $matches);
  }

  /**
   * Starts-with mode rejects mid-string matches.
   *
   * @covers ::collectEntityFieldMatches
   */
  public function testCollectEntityFieldMatchesRejectsSubstringInStartsWithMode(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service  = $this->createExposedService($provider);
    $entity   = $this->createFieldEntity(TRUE, [
      $this->createNameItem('Alice'),
    ]);

    $matches = $service->exposedCollectEntityFieldMatches(
      [$entity],
      'field_author',
      'given',
      'lic',
      10,
      'starts_with',
    );

    $this->assertSame([], $matches);
  }

  /**
   * Repeated values from multiple entities are deduplicated.
   *
   * @covers ::collectEntityFieldMatches
   */
  public function testCollectEntityFieldMatchesDeduplicatesValues(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service  = $this->createExposedService($provider);
    $entities = [
      $this->createFieldEntity(TRUE, [$this->createNameItem('Alice')]),
      $this->createFieldEntity(TRUE, [$this->createNameItem('Alice')]),
    ];

    $matches = $service->exposedCollectEntityFieldMatches(
      $entities,
      'field_author',
      'given',
      'al',
      10,
      'starts_with',
    );

    $this->assertSame(['Alice' => 'Alice'], $matches);
  }

  /**
   * Collection stops when the requested limit is reached.
   *
   * @covers ::collectEntityFieldMatches
   */
  public function testCollectEntityFieldMatchesHonorsLimit(): void {
    $provider = $this->createMock(NameOptionInterface::class);
    $service  = $this->createExposedService($provider);
    $entity   = $this->createFieldEntity(TRUE, [
      $this->createNameItem('Alice'),
      $this->createNameItem('Alfred'),
      $this->createNameItem('Alicia'),
    ]);

    $matches = $service->exposedCollectEntityFieldMatches(
      [$entity],
      'field_author',
      'given',
      'al',
      2,
      'starts_with',
    );

    $this->assertSame([
      'Alice' => 'Alice',
      'Alfred' => 'Alfred',
    ], $matches);
  }

}
