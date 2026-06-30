<?php

declare(strict_types=1);

namespace Drupal\name\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Translated labels and formatter output options for name components.
 *
 * @internal
 */
class NameComponentMetadataService implements NameComponentMetadataInterface {

  use StringTranslationTrait;

  /**
   * Cached component labels.
   *
   * @var array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string>|null
   */
  private ?array $translations = NULL;

  /**
   * Cached formatter output type labels.
   *
   * @var array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string>|null
   */
  private ?array $formatterOutputTypes = NULL;

  /**
   * Cached formatter output option labels.
   *
   * @var array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string>|null
   */
  private ?array $formatterOutputOptions = NULL;

  public function __construct(TranslationInterface $string_translation) {
    $this->setStringTranslation($string_translation);
  }

  /**
   * Returns translated labels for core name components.
   *
   * @param string[]|null $intersect
   *   Keys to include; empty or NULL returns all components.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string>
   *   Keyed component labels.
   */
  public function getTranslations(?array $intersect = NULL): array {
    if ($this->translations === NULL) {
      $this->translations = [
        'title' => $this->t('@name_title', ['@name_title' => $this->t('Title')]),
        'given' => $this->t('@name_given', ['@name_given' => $this->t('Given')]),
        'middle' => $this->t('@name_middle', ['@name_middle' => $this->t('Middle name(s)')]),
        'family' => $this->t('@name_family', ['@name_family' => $this->t('Family')]),
        'generational' => $this->t('@name_generational', ['@name_generational' => $this->t('Generational')]),
        'credentials' => $this->t('@name_credentials', ['@name_credentials' => $this->t('Credentials')]),
      ];
    }
    if ($intersect === NULL || $intersect === []) {
      return $this->translations;
    }
    return array_intersect_key($this->translations, $intersect);
  }

  /**
   * Labels for formatter output type identifiers.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string>
   *   Keys are output type machine names; values are labels.
   */
  public function getFormatterOutputTypes(): array {
    if ($this->formatterOutputTypes === NULL) {
      $this->formatterOutputTypes = [
        'default' => $this->t('Default'),
        'plain' => $this->t('Plain'),
        'raw' => $this->t('Raw'),
      ];
    }
    return $this->formatterOutputTypes;
  }

  /**
   * Select-option labels for formatter output settings.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string>
   *   Options suitable for #options on form elements.
   */
  public function getFormatterOutputOptions(): array {
    if ($this->formatterOutputOptions === NULL) {
      $this->formatterOutputOptions = [
        'default' => $this->t('Default'),
        'plain' => $this->t('Plain text'),
        'raw' => $this->t('Raw value (not recommended)'),
      ];
    }
    return $this->formatterOutputOptions;
  }

}
