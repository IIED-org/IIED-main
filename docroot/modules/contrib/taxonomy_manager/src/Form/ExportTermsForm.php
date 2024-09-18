<?php

declare(strict_types=1);

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\VocabularyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Form for creating a CSV export of all terms of given vocabulary.
 */
class ExportTermsForm extends FormBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new ExportTermsForm.
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->languageManager = $container->get('language_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_manager_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?VocabularyInterface $taxonomy_vocabulary = NULL) {
    // If no vocabulary found, throw an exception.
    if (!$taxonomy_vocabulary) {
      throw new \InvalidArgumentException('Invalid vocabulary.');
    }

    $form['export_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Terms to export'),
      '#options' => [
        'whole' => $this->t('Whole Vocabulary'),
        'root' => $this->t('Root Level Terms Only'),
      ],
      '#default_value' => 'whole',
    ];

    $form['fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Fields to export'),
      '#options' => $this->getFields($taxonomy_vocabulary),
      '#default_value' => $this->isTranslationEnabled() ? ['tid', 'name', 'langcode'] : ['tid', 'name'],
    ];

    $form['sort_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Sort by'),
      '#options' => $this->getFields($taxonomy_vocabulary),
      '#default_value' => 'name',
    ];

    if ($this->isTranslationEnabled()) {
      $form['languages'] = [
        '#type' => 'select',
        '#title' => $this->t('Languages to export'),
        '#options' => $this->getLanguagesOptions(),
        '#multiple' => TRUE,
        '#default_value' => ['all'],
      ];
    }

    $form['separator'] = [
      '#type' => 'radios',
      '#title' => $this->t('Separator'),
      '#options' => [
        ',' => $this->t('Comma'),
        "\t" => $this->t('Tab'),
        ';' => $this->t('Semicolon'),
      ],
      '#default_value' => ',',
    ];

    $form['strip_html'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strip HTML'),
      '#default_value' => TRUE,
    ];

    $form['trim_whitespace'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Trim Whitespace'),
      '#default_value' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export'),
    ];

    return $form;
  }

  /**
   * Gets the language options for a select element.
   *
   * @return array
   *   The language options list.
   */
  protected function getLanguagesOptions() {
    $options = ['all' => $this->t('All')];
    $languages = $this->languageManager->getLanguages();
    foreach ($languages as $language) {
      $options[$language->getId()] = $language->getName();
    }
    return $options;
  }

  /**
   * Whether we should use the multilingual capabilities of the vocabulary.
   *
   * @return bool
   *   True if translations enabled.
   */
  protected function isTranslationEnabled() {
    // Get the entity type definition for taxonomy terms.
    $entityType = $this->entityTypeManager->getDefinition('taxonomy_term');

    // Check if the translation handler is available.
    return $entityType->isTranslatable();
  }

  /**
   * Get available fields for the vocabulary.
   *
   * @param \Drupal\taxonomy\VocabularyInterface $vocabulary
   *   The vocabulary.
   */
  protected function getFields(VocabularyInterface $vocabulary) {
    $fields = [
      'tid' => $this->t('ID'),
      'name' => $this->t('Name'),
      'langcode' => $this->t('Language'),
      'status' => $this->t('Status'),
    ];

    // Add base and custom fields here.
    $termFields = $this->entityFieldManager->getFieldDefinitions('taxonomy_term', $vocabulary->id());
    foreach ($termFields as $fieldName => $fieldDefinition) {
      if (!isset($fields[$fieldName])) {
        $fields[$fieldName] = $fieldDefinition->getLabel();
      }
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get form values.
    $vocabularyId = $form_state->getBuildInfo()['args'][0]->id();
    $exportType = $form_state->getValue('export_type');
    $fields = array_filter($form_state->getValue('fields'));
    $separator = $form_state->getValue('separator');
    $stripHtml = (bool) $form_state->getValue('strip_html');
    $trimWhitespace = (bool) $form_state->getValue('trim_whitespace');
    $selectedLanguages = $form_state->getValue('languages');
    if (empty($selectedLanguages)) {
      $selectedLanguages = ['all'];
    }
    $sortField = $form_state->getValue('sort_field');

    // Load terms based on export type.
    $terms = $this->getTerms($vocabularyId, $exportType, $selectedLanguages);

    // Prepare CSV content.
    $csvContent = $this->prepareCsvContent($terms, $fields, $separator, $stripHtml, $trimWhitespace, $selectedLanguages, $sortField);

    // Create CSV file and return response for download.
    $filename = "{$vocabularyId}_export.csv";
    $filePath = $this->generateCsvFile($csvContent, $filename);

    $response = new BinaryFileResponse($filePath);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
    $response->deleteFileAfterSend(TRUE);
    $form_state->setResponse($response);
  }

  /**
   * Get terms based on the export type.
   *
   * @param string $vocabularyId
   *   The vocabulary id.
   * @param string $exportType
   *   The export type.
   * @param array $selectedLanguages
   *   The languages queried.
   *
   * @return array
   *   The terms.
   */
  protected function getTerms(string $vocabularyId, string $exportType, array $selectedLanguages): array {
    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    $query = $termStorage->getQuery()
      ->condition('vid', $vocabularyId)
      ->accessCheck(TRUE);

    if ($exportType == 'root') {
      $query->condition('parent', 0);
    }

    if (!in_array('all', $selectedLanguages)) {
      $query->condition('langcode', $selectedLanguages, 'IN');
    }

    $tids = $query->execute();
    return $termStorage->loadMultiple($tids);
  }

  /**
   * Prepare CSV content.
   *
   * @param \Drupal\taxonomy\TermInterface[] $terms
   *   The taxonomy terms.
   * @param array $fields
   *   The fields selected.
   * @param string $separator
   *   The separator.
   * @param bool $stripHtml
   *   The HTML stripping flag.
   * @param bool $trimWhitespace
   *   The whitespace trimming flag.
   * @param array $selectedLanguages
   *   The languages queried.
   * @param string $sortField
   *   The field name to sort the data by.
   *
   * @return array
   *   The CSV content.
   */
  protected function prepareCsvContent(array $terms, array $fields, string $separator, bool $stripHtml, bool $trimWhitespace, array $selectedLanguages, string $sortField): array {
    $rows = [];
    $header = [];

    // Prepare header row.
    foreach ($fields as $field) {
      $header[] = $this->escapeCsvValue($field, $separator);
    }

    // Add the BOM for UTF-8 encoding, and the header columns.
    $rows[] = "\xEF\xBB\xBF" . implode($separator, $header);

    // Collect and sort translations.
    $sortedTranslations = [];

    foreach ($terms as $term) {
      if (in_array('all', $selectedLanguages)) {
        foreach (array_keys($term->getTranslationLanguages(TRUE)) as $langcode) {
          $translation = $term->getTranslation($langcode);
          $sortedTranslations[] = [
            'term' => $translation,
            'sort_value' => $translation->get($sortField)->value,
          ];
        }
      }
      else {
        foreach ($selectedLanguages as $langcode) {
          if ($term->hasTranslation($langcode)) {
            $translation = $term->getTranslation($langcode);
            $sortedTranslations[] = [
              'term' => $translation,
              'sort_value' => $translation->get($sortField)->value,
            ];
          }
        }
      }
    }

    // Sort the translations by the sort field.
    usort($sortedTranslations, function ($a, $b) {
      return $a['sort_value'] <=> $b['sort_value'];
    });

    // Generate CSV rows from sorted translations.
    foreach ($sortedTranslations as $item) {
      $row = $this->prepareRow($item['term'], $fields, $separator, $stripHtml, $trimWhitespace);
      $rows[] = implode($separator, $row);
    }

    return $rows;
  }

  /**
   * Prepare a row for CSV.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The term.
   * @param array $fields
   *   The fields to show.
   * @param string $separator
   *   The separator.
   * @param bool $stripHtml
   *   The HTML stripping flag.
   * @param bool $trimWhitespace
   *   The whitespace trimming flag.
   *
   * @return array
   *   The row values as an array.
   */
  protected function prepareRow(TermInterface $term, array $fields, string $separator, bool $stripHtml, bool $trimWhitespace) {
    $row = [];
    foreach ($fields as $field) {
      $value = $term->get($field)->getString();
      if ($stripHtml) {
        $value = strip_tags($value);
      }
      if ($trimWhitespace) {
        $value = trim($value);
      }
      $row[] = $this->escapeCsvValue($value, $separator);
    }
    return $row;
  }

  /**
   * Escapes a value for CSV output, enclosing in double quotes if necessary.
   *
   * @param string $value
   *   The value to escape.
   * @param string $separator
   *   The separator character.
   *
   * @return string
   *   The escaped value.
   */
  protected function escapeCsvValue(string $value, string $separator) {
    // Escape double quotes by doubling them.
    $value = str_replace('"', '""', $value);

    // Enclose the value in double quotes if it contains the separator, a
    // double quote, or newlines.
    if (str_contains($value, $separator) || str_contains($value, '"') || str_contains($value, "\n")) {
      $value = '"' . $value . '"';
    }

    return $value;
  }

  /**
   * Generate CSV file.
   */
  protected function generateCsvFile($csvContent, $filename) {
    $filePath = 'temporary://' . $filename;
    $file = fopen($filePath, 'w');

    foreach ($csvContent as $row) {
      fwrite($file, $row . PHP_EOL);
    }

    fclose($file);
    return $filePath;
  }

}
