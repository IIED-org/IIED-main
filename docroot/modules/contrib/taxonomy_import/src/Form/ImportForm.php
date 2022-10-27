<?php

namespace Drupal\taxonomy_import\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contribute form.
 */
class ImportForm extends FormBase {

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
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityStorageInterface $vocabulary_storage) {
    $this->config = $config_factory->get('taxonomy_import.config');
    $this->vocabularyStorage = $vocabulary_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')->getStorage('taxonomy_vocabulary'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'import_taxonomy_form';
  }

  use StringTranslationTrait;

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
    $form['taxonomy_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Import file'),
      '#required' => TRUE,
      '#upload_validators'  => [
        'file_validate_extensions' => [$this->config->get('file_extensions') ?? ImportFormSettings::DEFAULT_FILE_EXTENSION],
        'file_validate_size' => [$this->config->get('file_max_size') ?? ImportFormSettings::DEFAULT_FILE_SIZE],
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
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      if ($key == 'field_vocabulary_name') {
        $voc_name = $value;
      }
    }
    create_taxonomy($voc_name);
  }

}

/**
 * Function to implement import taxonomy functionality.
 */
function create_taxonomy($voc_name) {
  global $base_url;
  $loc = Database::getConnection()->query('SELECT f.uri FROM {file_managed} f ORDER BY f.fid DESC limit 1', []);
  foreach ($loc as $val) {
    // Get location of the file.
    $location = $val->uri;
  }
  $name = $voc_name;
  if (function_exists('mime_content_type')) {
    $mimetype = mime_content_type($location);
  }
  else {
    return 'application/octet-stream';
  }
  // Converting to machine name.
  $machine_readable = strtolower($voc_name);
  // Vocabulary machine name.
  $vid = preg_replace('@[^a-z0-9_]+@', '_', $machine_readable);
  // Creating new vocabulary with the field value.
  $vocabularies = Vocabulary::loadMultiple();
  if (!isset($vocabularies[$vid])) {
    $vocabulary = Vocabulary::create([
      'vid' => $vid,
      'machine_name' => $vid,
      'name' => $name,
      'description' => '',
    ]);
    $vocabulary->save();
  }
  // Code for fetch and save csv file.
  if ($mimetype == "text/plain" || $mimetype == 'application/csv') {
    if (($handle = fopen($location, "r")) !== FALSE) {
      // Read all data including title.
      $data1 = fgetcsv($handle);
      while (($data = fgetcsv($handle)) !== FALSE) {
        $termid = 0;
        $term_id = 0;
        // Get tid of term with same name.
        $termid = Database::getConnection()->query('SELECT n.tid FROM {taxonomy_term_field_data} n WHERE n.name  = :uid AND n.vid  = :vid',
                  [':uid' => $data[0], ':vid' => $vid]);
        foreach ($termid as $val) {
          // Get tid.
          $term_id = $val->tid;
        }
        // Finding parent of new item.
        $parent = 0;
        if (!empty($data[1])) {
          $parent_id = Database::getConnection()->query('SELECT n.tid FROM {taxonomy_term_field_data} n WHERE n.name  = :uid AND n.vid  = :vid',
                       [':uid' => $data[1], ':vid' => $vid]);

          foreach ($parent_id as $val) {
            if (!empty($val)) {
              // Get tid.
              $parent = $val->tid;
            }
            else {
              $parent = 0;
            }
          }
        }
        $target_term = NULL;
        // Check whether term already exists or not.
        if (empty($term_id)) {
          // Create  new term.
          $term = Term::create([
            'parent' => [$parent],
            'name' => $data[0],
            'description' => '',
            'vid' => $vid,
          ])->save();
          if ($term == 1) {
            if (!empty($data[0])) {
              $properties['name'] = $data[0];
            }
            if (!empty($vid)) {
              $properties['vid'] = $vid;
            }
            $terms = \Drupal::EntityTypeManager()->getStorage('taxonomy_term')->loadByProperties($properties);
            $term = reset($terms);
            $target_term = $term;
          }
        }
        // Code to update existing term field(s)
        else {
          $term = \Drupal::EntityTypeManager()->getStorage('taxonomy_term')->load($term_id);
          $term->parent->setValue($parent);
          $term->Save();
          $target_term = $term;
        }
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
      fclose($handle);
      // Redirecting to taxonomy term overview page.
      $url = $base_url . "/admin/structure/taxonomy/manage/" . $vid . "/overview";
      header('Location:' . $url);
      exit;
    }
    else {
      \Drupal::messenger()->addStatus('File contains no data');
    }
  }
  // Code for fetch and save xml file.
  elseif (($mimetype == "text/xml") || ($mimetype == "application/xml")) {
    if (file_exists($location)) {
      $feed = file_get_contents($location);
      $items = simplexml_load_string($feed);

      if (!empty($items)) {

        $item = $items->children();
        foreach ($item as $child) {
          $records = $child;
          $array = (array) $records;
          $j = 0;
          foreach ($array as $val) {
            if ($j == 0) {
              $terms = $val;
            }
            if ($j == 1) {
              $parents = $val;
            }
            if ($j == 2) {
              $description = $val;
            }
            $j++;
            if ($j >= 3) {
              break;
            }
          }
          $parent = 0;
          $term_id = 0;
          // Checks if parent tag exists.
          if (isset($parents) && !empty($parents)) {
            $data = $parents;
            $parent_id = Database::getConnection()->query('SELECT n.tid FROM {taxonomy_term_field_data} n WHERE n.name  = :uid AND n.vid  = :vid',
                         [':uid' => $data, ':vid' => $vid]);
            foreach ($parent_id as $val) {
              if (!empty($val)) {
                // Get tid.
                $parent = $val->tid;
              }
              else {
                $parent = 0;
              }
            }
          }
          $termid = Database::getConnection()->query('SELECT n.tid FROM {taxonomy_term_field_data} n WHERE n.name  = :uid AND n.vid  = :vid',
                    [':uid' => $terms, ':vid' => $vid]);
          foreach ($termid as $val) {
            // Get tid.
            $term_id = $val->tid;
          }
          // Check whether term already exists or not.
          if (empty($term_id)) {
            // Create  new term.
            $term = Term::create([
              'parent' => [$parent],
              'name' => $terms,
              'description' => $description ?: '',
              'vid' => $vid,
            ])->save();
          }
          // Code to update existing term field(s)
          else {
            $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term_id);
            $term->parent->setValue($parent);
            $term->Save();
          }
        }
        // Redirecting to taxonomy term overview page.
        $url = $base_url . "/admin/structure/taxonomy/manage/" . $vid . "/overview";
        header('Location:' . $url);
        exit;
      }
      else {
        \Drupal::messenger()->addStatus('File contains no data');
      }
    }
  }
  elseif ($mimetype == "application/octet-stream") {
    \Drupal::messenger()->addStatus('File contains no data');
  }
  else {
    \Drupal::messenger()->addStatus('Failed to open the file');
  }
}
