<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Service\GeneratorInterface;
use Drupal\name\Service\GeneratorService;
use Drupal\name\Service\NameFormatParserInterface;
use Drupal\name\Service\NameFormatterInterface;

/**
 * @coversDefaultClass \Drupal\name\Service\GeneratorService
 *
 * @group name
 */
class NameGeneratorServiceTest extends UnitTestCase {

  /**
   * Field settings that enable all core name components (no filtering).
   *
   * @return array<string, mixed>
   *   Settings array with all name components enabled for field mocks.
   */
  private function allComponentsEnabledSettings(): array {
    return [
      'components' => [
        'title'        => TRUE,
        'given'        => TRUE,
        'middle'       => TRUE,
        'family'       => TRUE,
        'credentials'  => TRUE,
        'generational' => TRUE,
      ],
    ];
  }

  /**
   * Builds a config factory: each config name maps to key => value for get().
   *
   * @param array<string, array<string, mixed>> $map
   *   Config object name => data passed to ImmutableConfig::get($key).
   */
  private function createConfigFactory(array $map): ConfigFactoryInterface {
    $factory = $this->createMock(ConfigFactoryInterface::class);
    $factory->method('get')
      ->willReturnCallback(function (string $name) use ($map) {
        $immutable = $this->createMock('Drupal\Core\Config\ImmutableConfig');
        $data = $map[$name] ?? [];
        $immutable->method('get')
          ->willReturnCallback(function (string $key) use ($data) {
            return $data[$key] ?? NULL;
          });
        return $immutable;
      });
    return $factory;
  }

  /**
   * Creates GeneratorService with default mocks.
   *
   * @param array<string, array<string, mixed>> $configMap
   *   Map of config names to keys returned by ImmutableConfig::get().
   */
  private function createGenerator(array $configMap): GeneratorService {
    return new GeneratorService(
      $this->createMock(NameFormatterInterface::class),
      $this->createMock(NameFormatParserInterface::class),
      $this->createConfigFactory($configMap),
      $this->createMock(LanguageManagerInterface::class),
      $this->getStringTranslationStub(),
    );
  }

  /**
   * @covers ::__construct
   */
  public function testConstruct(): void {
    $formatter = $this->createMock(NameFormatterInterface::class);
    $parser    = $this->createMock(NameFormatParserInterface::class);
    $config    = $this->createConfigFactory([]);
    $language  = $this->createMock(LanguageManagerInterface::class);
    $translate = $this->getStringTranslationStub();

    $service = new GeneratorService($formatter, $parser, $config, $language, $translate);
    $this->assertInstanceOf(GeneratorInterface::class, $service);

    $reflection = new \ReflectionClass($service);
    $formatterProp = $reflection->getProperty('formatter');
    $formatterProp->setAccessible(TRUE);
    $this->assertSame($formatter, $formatterProp->getValue($service));

    $parserProp = $reflection->getProperty('parser');
    $parserProp->setAccessible(TRUE);
    $this->assertSame($parser, $parserProp->getValue($service));

    $configProp = $reflection->getProperty('configFactory');
    $configProp->setAccessible(TRUE);
    $this->assertSame($config, $configProp->getValue($service));

    $languageProp = $reflection->getProperty('languageManager');
    $languageProp->setAccessible(TRUE);
    $this->assertSame($language, $languageProp->getValue($service));
  }

  /**
   * @covers ::loadSampleValues
   * @covers ::loadConfiguration
   */
  public function testLoadSampleValuesLimitsResults(): void {
    $examples = [
      ['given' => 'A', 'family' => 'One'],
      ['given' => 'B', 'family' => 'Two'],
      ['given' => 'C', 'family' => 'Three'],
    ];
    $generator = $this->createGenerator([
      'name.generate.examples' => ['examples' => $examples],
    ]);

    $result = $generator->loadSampleValues(2);
    $this->assertCount(2, $result);
    $this->assertSame($examples[0], $result[0]);
    $this->assertSame($examples[1], $result[1]);
  }

  /**
   * @covers ::loadSampleValues
   */
  public function testLoadSampleValuesWithRandom(): void {
    $examples = [
      ['given' => 'A', 'family' => 'One'],
      ['given' => 'B', 'family' => 'Two'],
      ['given' => 'C', 'family' => 'Three'],
    ];
    $generator = $this->createGenerator([
      'name.generate.examples' => ['examples' => $examples],
    ]);

    $result = $generator->loadSampleValues(2, NULL, TRUE);
    $this->assertCount(2, $result);
    foreach ($result as $row) {
      $this->assertTrue(in_array($row, $examples, TRUE));
    }
  }

  /**
   * @covers ::loadSampleValues
   * @covers ::loadConfiguration
   */
  public function testLoadSampleValuesUsesFieldConfigWhenPresent(): void {
    $fieldExamples = [['given' => 'Field', 'family' => 'Only']];
    $globalExamples = [['given' => 'Global', 'family' => 'Wide']];
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getName')->willReturn('field_x');
    $field->method('getSettings')->willReturn($this->allComponentsEnabledSettings());

    $generator = $this->createGenerator([
      'name.generate.examples.field_x' => ['examples' => $fieldExamples],
      'name.generate.examples'           => ['examples' => $globalExamples],
    ]);

    $result = $generator->loadSampleValues(3, $field);
    $this->assertSame($fieldExamples, $result);
  }

  /**
   * @covers ::loadSampleValues
   * @covers ::loadConfiguration
   */
  public function testLoadSampleValuesFallsBackToGlobalWhenFieldConfigEmpty(): void {
    $globalExamples = [['given' => 'Global', 'family' => 'Wide']];
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getName')->willReturn('field_x');
    $field->method('getSettings')->willReturn($this->allComponentsEnabledSettings());

    $generator = $this->createGenerator([
      'name.generate.examples.field_x' => ['examples' => []],
      'name.generate.examples'           => ['examples' => $globalExamples],
    ]);

    $result = $generator->loadSampleValues(1, $field);
    $this->assertSame($globalExamples, $result);
  }

  /**
   * @covers ::loadSampleValues
   * @covers ::filterByFieldSettings
   */
  public function testLoadSampleValuesFiltersByFieldSettings(): void {
    $examples = [
      [
        'title'    => 'Mr',
        'given'    => 'John',
        'middle'   => 'P',
        'family'   => 'Doe',
        'preferred' => 'Joe',
      ],
    ];
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getName')->willReturn('field_name');
    $field->method('getSettings')->willReturn([
      'components' => [
        'title'          => FALSE,
        'given'          => TRUE,
        'middle'         => FALSE,
        'family'         => TRUE,
        'credentials'    => FALSE,
        'generational'   => FALSE,
      ],
    ]);

    $generator = $this->createGenerator([
      'name.generate.examples.field_name' => ['examples' => $examples],
      'name.generate.examples'             => ['examples' => []],
    ]);

    $result = $generator->loadSampleValues(3, $field);
    $this->assertCount(1, $result);
    $this->assertSame([
      'given'     => 'John',
      'family'    => 'Doe',
      'preferred' => 'Joe',
    ], $result[0]);
  }

  /**
   * @covers ::generateSampleNames
   * @covers ::initComponents
   * @covers ::buildName
   * @covers ::pickRandom
   * @covers ::loadConfiguration
   */
  public function testGenerateSampleNamesReturnsCountAndStructure(): void {
    $gender = [
      'female' => [
        'given'          => ['Jane'],
        'family'         => ['Doe'],
        'title'          => ['Ms'],
        'middle'         => ['Q'],
        'credentials'    => ['MBA'],
        'generational'   => ['Jr'],
      ],
      'male'   => [
        'given'          => ['John'],
        'family'         => ['Smith'],
        'title'          => ['Mr'],
        'middle'         => ['P'],
        'credentials'    => ['PhD'],
        'generational'   => ['Sr'],
      ],
    ];
    $generator = $this->createGenerator([
      'name.generate.components' => [
        'components' => [],
        'gender'     => $gender,
      ],
      'name.generate.preferred' => [
        'preferred' => [
          'Jane' => 'Janie',
          'John' => 'Jack',
        ],
      ],
    ]);

    $names = $generator->generateSampleNames(5);
    $this->assertCount(5, $names);
    $expectedKeys = [
      'title',
      'given',
      'middle',
      'family',
      'generational',
      'credentials',
    ];
    foreach ($names as $name) {
      foreach ($expectedKeys as $key) {
        $this->assertArrayHasKey($key, $name);
      }
      $this->assertNotSame('', $name['given']);
      $this->assertNotSame('', $name['family']);
      $this->assertContains($name['given'], ['Jane', 'John']);
      $this->assertContains($name['family'], ['Doe', 'Smith']);
    }
  }

  /**
   * @covers ::generateSampleNames
   * @covers ::initComponents
   * @covers ::loadConfiguration
   */
  public function testInitComponentsMergesGenderlessAndGenderConfig(): void {
    $generator = $this->createGenerator([
      'name.generate.components' => [
        'components' => [
          'given'        => ['Shared'],
          'family'       => ['Base'],
          'title'        => ['Mx'],
          'middle'       => ['A'],
          'credentials'  => ['PMP'],
          'generational' => ['III'],
        ],
        'gender' => [
          'female' => [
            'given'  => ['Jane'],
            'family' => ['Doe'],
          ],
          'male'   => [
            'given'  => ['John'],
            'family' => ['Smith'],
          ],
        ],
      ],
      'name.generate.preferred' => ['preferred' => []],
    ]);

    $generator->generateSampleNames(1);

    $reflection = new \ReflectionClass($generator);
    $property = $reflection->getProperty('components');
    $property->setAccessible(TRUE);
    $components = $property->getValue($generator);

    $this->assertSame(['Shared', 'Jane'], $components['female']['given']);
    $this->assertSame(['Base', 'Doe'], $components['female']['family']);
    $this->assertSame(['Shared', 'John'], $components['male']['given']);
    $this->assertSame(['Base', 'Smith'], $components['male']['family']);
  }

  /**
   * @covers ::generateSampleNames
   * @covers ::initComponents
   * @covers ::buildName
   * @covers ::pickRandom
   * @covers ::loadConfiguration
   */
  public function testInitComponentsEarlyReturnOnSecondCall(): void {
    $generator = $this->createGenerator([
      'name.generate.components' => [
        'components' => [
          'given'        => ['Shared'],
          'family'       => ['Base'],
          'title'        => ['Mx'],
          'middle'       => ['A'],
          'credentials'  => ['PMP'],
          'generational' => ['III'],
        ],
        'gender' => [
          'female' => [
            'given'  => ['Jane'],
            'family' => ['Doe'],
          ],
          'male'   => [
            'given'  => ['John'],
            'family' => ['Smith'],
          ],
        ],
      ],
      'name.generate.preferred' => ['preferred' => []],
    ]);

    $generator->generateSampleNames(1);

    $reflection = new \ReflectionClass($generator);
    $property = $reflection->getProperty('components');
    $property->setAccessible(TRUE);
    $components = $property->getValue($generator);
    $components['female']['preferred'] = ['sentinel'];
    $components['male']['preferred'] = ['sentinel'];
    $property->setValue($generator, $components);

    $generator->generateSampleNames(1);

    $this->assertSame($components, $property->getValue($generator));
  }

  /**
   * @covers ::generateSampleNames
   * @covers ::initComponents
   * @covers ::buildName
   * @covers ::pickRandom
   * @covers ::loadConfiguration
   */
  public function testBuildNameAlwaysHasGivenAndFamily(): void {
    $gender = [
      'female' => [
        'given'        => ['Jane'],
        'family'       => ['Doe'],
        'title'        => ['Ms'],
        'middle'       => ['Q'],
        'credentials'  => ['MBA'],
        'generational' => ['Jr'],
      ],
      'male'   => [
        'given'        => ['John'],
        'family'       => ['Smith'],
        'title'        => ['Mr'],
        'middle'       => ['P'],
        'credentials'  => ['PhD'],
        'generational' => ['Sr'],
      ],
    ];
    $generator = $this->createGenerator([
      'name.generate.components' => [
        'components' => [],
        'gender'     => $gender,
      ],
      'name.generate.preferred' => ['preferred' => []],
    ]);

    mt_srand(42);
    $names = $generator->generateSampleNames(20);

    foreach ($names as $name) {
      $this->assertNotSame('', $name['given']);
      $this->assertNotSame('', $name['family']);
    }
  }

  /**
   * @covers ::generateSampleNames
   * @covers ::initComponents
   * @covers ::buildName
   * @covers ::pickRandom
   * @covers ::filterByFieldSettings
   */
  public function testGenerateSampleNamesFiltersWhenFieldProvided(): void {
    $gender = [
      'female' => [
        'given'          => ['Jane'],
        'family'         => ['Doe'],
        'title'          => ['Ms'],
        'middle'         => ['Q'],
        'credentials'    => ['MBA'],
        'generational'   => ['Jr'],
      ],
      'male'   => [
        'given'          => ['John'],
        'family'         => ['Smith'],
        'title'          => ['Mr'],
        'middle'         => ['P'],
        'credentials'    => ['PhD'],
        'generational'   => ['Sr'],
      ],
    ];
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getName')->willReturn('field_name');
    $field->method('getSettings')->willReturn([
      'components' => [
        'title'          => TRUE,
        'given'          => TRUE,
        'middle'         => FALSE,
        'family'         => TRUE,
        'credentials'    => FALSE,
        'generational'   => FALSE,
      ],
    ]);

    $generator = $this->createGenerator([
      'name.generate.components.field_name' => [
        'components' => [],
        'gender'     => $gender,
      ],
      'name.generate.components' => [
        'components' => [],
        'gender'     => [],
      ],
      'name.generate.preferred.field_name' => ['preferred' => []],
      'name.generate.preferred'            => ['preferred' => []],
    ]);

    mt_srand(42);
    $names = $generator->generateSampleNames(10, $field);
    $this->assertCount(10, $names);
    foreach ($names as $name) {
      $this->assertArrayHasKey('given', $name);
      $this->assertArrayHasKey('family', $name);
      $this->assertArrayNotHasKey('middle', $name);
      $this->assertArrayNotHasKey('credentials', $name);
      $this->assertArrayNotHasKey('generational', $name);
    }
  }

}
