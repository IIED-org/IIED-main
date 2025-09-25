<?php

namespace Drupal\taxonomy_import\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy_import\Service\TaxonomyUtilsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contribute form.
 */
class ImportForm extends FormBase {

  use StringTranslationTrait;

  private const ALLOWED_MIME_TYPES = [
    'text/plain',
    'application/csv',
    'text/csv',
    'text/xml',
    'application/xml',
  ];

  private const CSV_MIME_TYPES = [
    'text/plain',
    'application/csv',
    'text/csv',
  ];

  /**
   * Config of Taxonomy import module.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The vocabulary storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $vocabularyStorage;

  /**
   * The taxonomy utilities.
   *
   * @var \Drupal\taxonomy_import\Service\TaxonomyUtilsInterface
   */
  protected $taxonomyUtils;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityStorageInterface $vocabulary_storage, TaxonomyUtilsInterface $taxonomy_utils) {
    $this->config = $config_factory->get('taxonomy_import.config');
    $this->vocabularyStorage = $vocabulary_storage;
    $this->taxonomyUtils = $taxonomy_utils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')->getStorage('taxonomy_vocabulary'),
      $container->get('taxonomy_import.term_utils')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'import_taxonomy_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $vocabularies = $this->vocabularyStorage->loadMultiple();
    $vocabulariesList = [];
    foreach ($vocabularies as $vid => $vocablary) {
      $vocabulariesList[$vid] = $vocablary->get('name');
    }
    $form['field_vocabulary_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Vocabulary name'),
      '#options' => $vocabulariesList,
      '#attributes' => [
        'class' => ['vocab-name-select'],
      ],
      '#description' => $this->t('Select vocabulary!'),
    ];
    $form['import_behavior'] = [
      '#type' => 'select',
      '#title' => $this->t('Force new terms for every record of Source Data'),
      '#options' => [
        0 => $this->t('No, allow updating of existing records in term name matches'),
        1 => $this->t('Yes, create a new term for every record; do not update existing'),
      ],
      '#description' => $this->t('Select desired import behavior!'),
    ];
    $form['taxonomy_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Import file'),
      '#required' => TRUE,
      '#upload_validators'  => [
        'FileExtension' => ['extensions' => $this->config->get('file_extensions') ?? ImportFormSettings::DEFAULT_FILE_EXTENSION],
        'FileSizeLimit' => ['fileLimit' => $this->config->get('file_max_size') ?? ImportFormSettings::DEFAULT_FILE_SIZE],
      ],
      '#upload_location' => 'public://taxonomy_files/',
      '#description' => $this->t('Upload a file to Import taxonomy!') . $this->config->get('file_max_size'),
    ];
    $form['actions']['#type'] = 'actions';
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $fileErrorMessage = $this->t('File was not provided or cannot be read.');

    $vid = $form_state->getValue('field_vocabulary_name');
    $ary = $form_state->getValue('taxonomy_file');
    $importBehavior = $form_state->getValue('import_behavior');
    $fid = !empty($ary[0]) ? $ary[0] : NULL;

    if (!$vid) {
      $form_state->setErrorByName('field_vocabulary_name', $this->t('Vocabulary name was not provided.'));

      return;
    }

    if (!isset($importBehavior)) {
      $form_state->setErrorByName('import_behavior', $this->t('Import behavior was not provided.'));

      return;
    }

    $file = $fid ? \Drupal::entityTypeManager()->getStorage('file')->load($fid) : NULL;
    if (!$file) {
      $form_state->setErrorByName('taxonomy_file', $fileErrorMessage);

      return;
    }

    $filepath = $file->uri->value;
    $mimetype = $file->filemime->value;

    if (!$filepath) {
      $form_state->setErrorByName('taxonomy_file', $fileErrorMessage);

      return;
    }

    if (!in_array($mimetype, self::ALLOWED_MIME_TYPES)) {
      $form_state->setErrorByName('taxonomy_file', $this->t('File is not of a supported type.'));

      return;
    }

    $form_state->set('vid', $vid);
    $form_state->set('import_behavior', $importBehavior);
    $form_state->set('filepath', $filepath);
    $form_state->set('is_csv', in_array($mimetype, self::CSV_MIME_TYPES));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $vid = $form_state->get('vid');
    $filepath = $form_state->get('filepath');
    $importBehavior = $form_state->getValue('import_behavior');

    if ($form_state->get('is_csv')) {
      $rows = $this->readCsv($vid, $filepath);
    }
    else {
      $rows = $this->readXml($vid, $filepath);
    }

    if (!$rows) {
      throw new \Exception($this->t('File @filepath contained no rows, please check the file.', ['@filepath' => $filepath]));
    }

    /* @todo: move field verification here, and then pass to saveTerms */

    $this->taxonomyUtils->saveTerms($vid, $rows, $importBehavior);

    $url = $this->t('admin/structure/taxonomy/manage/:vid/overview', [':vid' => $vid]);

    $url = \Drupal::service('path.validator')->getUrlIfValid($url);

    if ($url) {
      $form_state->setRedirectUrl($url);
    }
  }

  /**
   * Function to read a CSV file.
   *
   * Note that this skips the first line. Previous functionality was to
   * set a term field based on the first line if there were more than 2
   * items in the first line. That functionality has been removed for now.
   *
   * @return array
   *   This is an array of arrays, each with keys 'name', 'parent', and
   *   'description'.
   * @throws \Exception
   */
  protected function readCsv($vid, $filepath) {
    // Code for fetch and save csv file.
    $handle = fopen($filepath, 'r');
    if (!$handle) {
      throw new \Exception($this->t('File @filepath cannot be opened.', ['@filepath' => $filepath]));
    }

    $items = [];

    // Skip the first line.
    $unused = fgetcsv($handle);
    while (($data = fgetcsv($handle)) !== FALSE) {
      if (empty($data[0])) {
        continue;
      }

      $items[] = [
        'name' => $data[0],
        'parent' => !empty($data[1]) ? $data[1] : '',
        'description' => !empty($data[2]) ? $data[2] : '',
      ];
    }

    fclose($handle);

    return $items;
  }

  /**
   * Function to read an XML file.
   *
   * Note that this did not have the same first line functionality as
   * readCsv.
   *
   * @see readCsv
   *
   * @return array
   *   This is an array of arrays, each with keys 'name', 'parent', and
   *   'description'.
   */
  protected function readXml($vid, $filepath) {
    // Code for fetch and save xml file.
    $contents = file_get_contents($filepath);
    $rawItems = $contents ? simplexml_load_string($contents) : NULL;
    if (empty($rawItems)) {
      throw new \Exception($this->t('File @filepath cannot be opened.', ['@filepath' => $filepath]));
    }

    $items = [];
    foreach ($rawItems->children() as $item) {
      $item = (array) $item;
      if (empty($item['name'])) {
        continue;
      }

      $items[] = $item;
    }

    return $items;
  }

  /**
   * Currently unused code.
   *
   * This was copied from the part that sets additional fields.
   *
   * @todo rewrite or delete if that feature isn't wanted.
   */
  protected function setAdditionalFields($vocabularyName) {
    if (count($data1) > 2 && !empty($target_term)) {
      $i = 2;
      $update = FALSE;
      while ($i < count($data1)) {
        if (!empty($data[$i]) && !empty($data1[$i])) {
          $target_term->set($data1[$i], $data[$i]);
          $update = TRUE;
        }
        $i++;
      }
      if ($update) {
        $target_term->save();
      }
    }
  }

}
