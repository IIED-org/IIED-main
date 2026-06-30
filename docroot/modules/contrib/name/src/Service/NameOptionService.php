<?php

declare(strict_types=1);

namespace Drupal\name\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Options provider for name field components.
 */
class NameOptionService implements NameOptionInterface {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The module handler.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The term storage manager.
   *
   * @var \Drupal\taxonomy\TermStorageInterface|null
   */
  protected $termStorage;

  /**
   * The vocabulary storage manager.
   *
   * @var \Drupal\taxonomy\VocabularyStorageInterface|null
   */
  protected $vocabularyStorage;

  /**
   * Constructs a NameOptionService object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;

    if ($this->entityTypeManager && $this->moduleHandler->moduleExists('taxonomy')) {
      $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
      $this->vocabularyStorage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(FieldDefinitionInterface $field, string $component): array {
    $fs = $field->getSettings();
    // Field settings may store TranslatableMarkup or other Stringable values.
    $options = array_map(
      static fn ($item): string => (string) $item,
      (array) ($fs[$component . '_options'] ?? [])
    );
    foreach ($options as $index => $opt) {
      if (preg_match(NameOptionInterface::VOCABULARY_REGEX, trim($opt), $matches)) {
        unset($options[$index]);
        if ($this->termStorage && $this->vocabularyStorage) {
          $vocabulary = $this->vocabularyStorage->load($matches[1]);
          if ($vocabulary) {
            $max_length = $fs['max_length'][$component] ?? 255;
            foreach ($this->termStorage->loadTree($vocabulary->id()) as $term) {
              if (mb_strlen($term->name) <= $max_length) {
                $options[] = $term->name;
              }
            }
          }
        }
      }
    }

    // Options could come from multiple sources, filter duplicates.
    $options = array_unique($options);

    if (isset($fs['sort_options']) && !empty($fs['sort_options'][$component])) {
      natcasesort($options);
    }
    $default = FALSE;
    foreach ($options as $index => $opt) {
      if (strpos($opt, '--') === 0) {
        unset($options[$index]);
        $default = trim(mb_substr($opt, 2));
      }
    }
    $options = array_map(
      static fn (string $value): string => trim($value),
      $options
    );
    $options = array_combine($options, $options);
    if ($default !== FALSE) {
      $options = ['_none' => $default] + $options;
    }
    return $options;
  }

}
