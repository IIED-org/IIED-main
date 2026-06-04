<?php

declare(strict_types=1);

namespace Drupal\name\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\name\Utility\NameComponents;

/**
 * Handles name generation.
 */
class GeneratorService implements GeneratorInterface {

  use StringTranslationTrait;

  /**
   * The name formatter.
   */
  protected NameFormatterInterface $formatter;

  /**
   * The name format parser.
   */
  protected NameFormatParserInterface $parser;

  /**
   * The factory for configuration objects.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Language manager for retrieving the default language code if needed.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * An array of gender sorted components for generating random names.
   *
   * @var array
   */
  protected $components = [];

  /**
   * Constructs a name formatter object.
   *
   * @param \Drupal\name\Service\NameFormatterInterface $formatter
   *   The name formatter.
   * @param \Drupal\name\Service\NameFormatParserInterface $parser
   *   The name format parser.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation.
   */
  public function __construct(NameFormatterInterface $formatter, NameFormatParserInterface $parser, ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager, TranslationInterface $translation) {
    $this->formatter = $formatter;
    $this->parser = $parser;
    $this->configFactory = $config_factory;
    $this->languageManager = $language_manager;
    $this->stringTranslation = $translation;
  }

  /**
   * {@inheritdoc}
   */
  public function generateSampleNames($limit = 3, ?FieldDefinitionInterface $field_definition = NULL) {
    $this->initComponents($field_definition);
    $preferred = $this->loadConfiguration('name.generate.preferred', 'preferred', $field_definition);

    $names = [];
    for ($i = 0; $i < $limit; $i++) {
      $gender = rand(0, 1) ? 'male' : 'female';
      $names[] = $this->buildName($gender, $preferred ?? []);
    }

    if ($field_definition) {
      $names = $this->filterByFieldSettings($field_definition, $names);
    }

    return $names;
  }

  /**
   * Initializes configured component pools for random name generation.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface|null $field_definition
   *   The field definition to find field specific configuration.
   */
  private function initComponents(?FieldDefinitionInterface $field_definition = NULL): void {
    if ($this->components) {
      return;
    }

    $keys = NameComponents::coreKeys() + [
      'preferred'   => 'preferred',
      'alternative' => 'alternative',
    ];
    $this->components = [
      'female' => array_fill_keys($keys, []),
      'male'   => array_fill_keys($keys, []),
    ];

    // Parse genderless configuration.
    $components = $this->loadConfiguration('name.generate.components', 'components', $field_definition);
    foreach ($keys as $key) {
      if (isset($components[$key])) {
        $this->components['female'][$key] = $components[$key];
        $this->components['male'][$key] = $components[$key];
      }
    }

    // Parse gender configuration.
    $components = $this->loadConfiguration('name.generate.components', 'gender', $field_definition);
    foreach (['female', 'male'] as $gender) {
      foreach ($keys as $key) {
        if (isset($components[$gender][$key])) {
          $this->components[$gender][$key] = array_merge($this->components[$gender][$key], $components[$gender][$key]);
        }
      }
    }
  }

  /**
   * Builds a random name array for a gender component pool.
   *
   * @param string $gender
   *   The gender component pool to use.
   * @param array<string, string> $preferred
   *   Preferred names keyed by given name.
   *
   * @return array<string, mixed>
   *   A random name component array.
   */
  private function buildName(string $gender, array $preferred): array {
    $name = [
      'title'        => '',
      'given'        => $this->pickRandom($this->components[$gender]['given']),
      'middle'       => '',
      'family'       => $this->pickRandom($this->components[$gender]['family']),
      'generational' => '',
      'credentials'  => '',
    ];

    if (rand(0, 2)) {
      $name['title'] = $this->pickRandom($this->components[$gender]['title']);
    }
    if (rand(0, 1)) {
      $name['middle'] = $this->pickRandom($this->components[$gender]['middle']);
    }
    if (rand(0, 1)) {
      $credentials = [];
      $credential_count = count($this->components[$gender]['credentials']);
      $cred_limit = min([rand(1, 3), $credential_count]);
      for ($j = 0; $j <= $cred_limit; $j++) {
        $credentials[] = $this->pickRandom($this->components[$gender]['credentials']);
      }
      $name['credentials'] = implode(', ', $credentials);
    }
    if (!rand(0, 2)) {
      $name['generational'] = $this->pickRandom($this->components[$gender]['generational']);
    }
    // All defined names have a preferred alternative, randomize it slightly.
    if (rand(0, 1) && !empty($name['given']) && !empty($preferred[$name['given']])) {
      $name['preferred'] = $preferred[$name['given']];
    }

    return $name;
  }

  /**
   * Picks a random value from a component pool.
   *
   * @param array<int, mixed> $pool
   *   The component pool.
   *
   * @return mixed
   *   A random component value.
   */
  private function pickRandom(array $pool): mixed {
    return $pool[array_rand($pool)];
  }

  /**
   * {@inheritdoc}
   */
  public function loadSampleValues($limit = 3, ?FieldDefinitionInterface $field_definition = NULL, $random = FALSE) {
    $example_names = $this->loadConfiguration('name.generate.examples', 'examples', $field_definition) ?? [];

    // Randomly shuffle and get the required count.
    if ($random) {
      shuffle($example_names);
    }

    $example_names = array_slice($example_names, 0, $limit);

    // Filter to the enabled components if we have field context.
    if ($field_definition) {
      $example_names = $this->filterByFieldSettings($field_definition, $example_names);
    }

    return $example_names;
  }

  /**
   * Helper function to load the settings.
   *
   * @param string $config
   *   The configuration to load.
   * @param string $key
   *   The configuration key to retrieve.
   * @param \Drupal\Core\Field\FieldDefinitionInterface|null $field_definition
   *   The field definition to find field specific configuration.
   *
   * @return array
   *   The array of settings.
   *
   * @throws \Drupal\Core\Config\ConfigException
   *   An error if the global configuration is empty or missing.
   */
  protected function loadConfiguration($config, $key, ?FieldDefinitionInterface $field_definition = NULL) {
    $components = [];
    if ($field_definition) {
      $components = $this->configFactory->get($config . '.' . $field_definition->getName())->get($key);
    }
    if (!$components) {
      $components = $this->configFactory->get($config)->get($key);
    }

    return $components;
  }

  /**
   * Helper function to filter the name components by the field definition.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition if in context.
   * @param array $example_names
   *   Array of names to filter.
   *
   * @return array
   *   A nested array of name components.
   */
  protected function filterByFieldSettings(FieldDefinitionInterface $field_definition, array $example_names = []) {
    $settings = $field_definition->getSettings();
    $components = array_keys(array_filter($settings['components']));
    $components = array_combine($components, $components);
    foreach ($example_names as $delta => $example_name) {
      $example_names[$delta] = array_intersect_key($example_name, $components);
      // This allows non-field defined properties like preferred through.
      $example_names[$delta] += array_diff_key($example_name, NameComponents::coreKeys());
    }
    return $example_names;
  }

}
