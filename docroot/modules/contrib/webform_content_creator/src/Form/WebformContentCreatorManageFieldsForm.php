<?php

namespace Drupal\webform_content_creator\Form;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\webform_content_creator\WebformContentCreatorUtilities;

/**
 * Form handler for the Webform content creator manage fields form.
 */
class WebformContentCreatorManageFieldsForm extends EntityForm {

  const BUNDLE_FIELD = 'bundle_field';

  const FIELD_TYPE = 'field_type';

  const WEBFORM_FIELD = 'webform_field';

  const FIELD_MAPPING = 'mapping';

  const CUSTOM_CHECK = 'custom_check';

  const CUSTOM_VALUE = 'custom_value';

  const FORM_TABLE = 'table';

  const ELEMENTS = 'elements';

  /**
   * Plugin field type.
   *
   * @var object
   */
  protected $pluginFieldType;

  /**
   * Entity type manager.
   *
   * @var object
   */
  protected $entityTypeManager;

  /**
   * Field mapping manager.
   *
   * @var object
   */
  protected $fieldMappingManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->pluginFieldType = $container->get('plugin.manager.field.field_type');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->fieldMappingManager = $container->get('plugin.manager.webform_content_creator.field_mapping');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['intro_text'] = [
      '#markup' => '<p>' . $this->t('You can create nodes based on webform submission values. In this page, you can do mappings between content type fields and webform submission values. You may also use tokens in custom text.') . '</p>',
    ];
    if (\Drupal::service('module_handler')->moduleExists('token')) {
      $form['tokens'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['webform_submission'],
        '#global_types' => TRUE,
        '#click_insert' => TRUE,
        '#show_restricted' => FALSE,
        '#recursion_limit' => 3,
        '#text' => $this->t('Browse available tokens'),
      ];
    }
    // Construct table with mapping between content type and webform.
    $this->constructTable($form);
    return $form;
  }

  /**
   * Constructs table with mapping between webform and content type.
   *
   * @param array $form
   *   Form entity array.
   */
  public function constructTable(array &$form) {
    $fieldTypesDefinitions = $this->pluginFieldType->getDefinitions();
    $attributes = $this->entity->getAttributes();
    $entity_type_initial_id = $this->entity->getEntityTypeValue();
    $entity_type_id = $this->entity->getEntityTypeValue();
    $bundle_id = $this->entity->getBundleValue();
    if ($entity_type_id === 'node') {
      $entity_type_id = 'node_type';
    }

    $bundleFilteredfield_ids = WebformContentCreatorUtilities::getBundleIds($entity_type_initial_id, $bundle_id);
    asort($bundleFilteredfield_ids);
    $bundleFields = WebformContentCreatorUtilities::bundleFields($entity_type_initial_id, $bundle_id);
    $webform_id = $this->entity->getWebform();
    $webformOptions = WebformContentCreatorUtilities::getWebformElements($webform_id);

    // Table header.
    $header = [
      self::BUNDLE_FIELD => $this->t('Bundle field'),
      self::FIELD_TYPE => $this->t('Field type'),
      self::FIELD_MAPPING => $this->t('Field mapping'),
      self::CUSTOM_CHECK => $this->t('Custom'),
      self::WEBFORM_FIELD => $this->t('Webform field'),
      self::CUSTOM_VALUE => $this->t('Custom text'),
    ];
    $form[self::FORM_TABLE] = [
      '#type' => 'table',
      '#header' => $header,
    ];

    foreach ($bundleFilteredfield_ids as $field_id) {
      // Checkboxes with bundle fields.
      $form[self::FORM_TABLE][$field_id][self::BUNDLE_FIELD] = [
        '#type' => 'checkbox',
        '#default_value' => array_key_exists($field_id, $attributes),
        '#title' => $bundleFields[$field_id]->getLabel() . ' (' . $field_id . ')',
      ];

      // Link to edit field settings.
      $form[self::FORM_TABLE][$field_id][self::FIELD_TYPE] = [
        '#type' => 'markup',
        '#markup' => $fieldTypesDefinitions[$bundleFields[$field_id]->getType()]['label'],
      ];

      // Find available field mappings for the element type.
      $field_mapping_options = [];
      $field_mapping_options['default_mapping'] = $this->t('Default');
      foreach ($this->fieldMappingManager->getFieldMappings($bundleFields[$field_id]->getType()) as $field_mapping) {
        if ($field_mapping->getId() === 'default_mapping') {
          continue;
        }
        $field_mapping_options[$field_mapping->getId()] = $field_mapping->getLabel();
      }

      // Select the field mapping.
      $default_value = array_key_exists($field_id, $attributes) && isset($attributes[$field_id][self::FIELD_MAPPING]) ? $attributes[$field_id][self::FIELD_MAPPING] : '';
      $form[self::FORM_TABLE][$field_id][self::FIELD_MAPPING] = [
        '#type' => 'select',
        '#options' => $field_mapping_options,
        '#default_value' => $default_value,
        '#states' => [
          'visible' => [
            ':input[name="' . self::FORM_TABLE . '[' . $field_id . '][' . self::BUNDLE_FIELD . ']"]' => ['checked' => TRUE],
          ],
        ],
      ];

      // Checkbox to select between webform element/property or custom text.
      $form[self::FORM_TABLE][$field_id][self::CUSTOM_CHECK] = [
        '#type' => 'checkbox',
        '#default_value' => array_key_exists($field_id, $attributes) ? $attributes[$field_id][self::CUSTOM_CHECK] : '',
        '#states' => [
          'visible' => [
            ':input[name="' . self::FORM_TABLE . '[' . $field_id . '][' . self::BUNDLE_FIELD . ']"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $type = !empty($attributes[$field_id]) && $attributes[$field_id]['type'] ? '1' : '0';

      // Select with webform elements and basic properties.
      $form[self::FORM_TABLE][$field_id][self::WEBFORM_FIELD] = [
        '#type' => 'select',
        '#options' => $webformOptions,
        '#states' => [
          'required' => [
            ':input[name="' . self::FORM_TABLE . '[' . $field_id . '][' . self::BUNDLE_FIELD . ']"]' => ['checked' => TRUE],
            ':input[name="' . self::FORM_TABLE . '[' . $field_id . '][' . self::CUSTOM_CHECK . ']"]' => ['checked' => FALSE],
          ],
          'visible' => [
            ':input[name="' . self::FORM_TABLE . '[' . $field_id . '][' . self::BUNDLE_FIELD . ']"]' => ['checked' => TRUE],
            ':input[name="' . self::FORM_TABLE . '[' . $field_id . '][' . self::CUSTOM_CHECK . ']"]' => ['checked' => FALSE],
          ],
        ],
      ];

      if (array_key_exists($field_id, $attributes) && !$attributes[$field_id][self::CUSTOM_CHECK]) {
        $form[self::FORM_TABLE][$field_id][self::WEBFORM_FIELD]['#default_value'] = $type . ',' . $attributes[$field_id][self::WEBFORM_FIELD];
      }

      // Textarea with custom text (including tokens)
      $form[self::FORM_TABLE][$field_id][self::CUSTOM_VALUE] = [
        '#type' => 'textarea',
        '#default_value' => array_key_exists($field_id, $attributes) ? $attributes[$field_id][self::CUSTOM_VALUE] : '',
        '#states' => [
          'visible' => [
            ':input[name="' . self::FORM_TABLE . '[' . $field_id . '][' . self::BUNDLE_FIELD . ']"]' => ['checked' => TRUE],
            ':input[name="' . self::FORM_TABLE . '[' . $field_id . '][' . self::CUSTOM_CHECK . ']"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    // Change table position in page.
    $form[self::FORM_TABLE]['#weight'] = 1;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $attributes = [];
    // For each table row.
    foreach ($form_state->getValue(self::FORM_TABLE) as $k => $v) {
      // Check if a bundle field is selected.
      if (!$v[self::BUNDLE_FIELD]) {
        continue;
      }
      $args = explode(',', $v[self::WEBFORM_FIELD]);
      if (empty($args) || count($args) < 1) {
        continue;
      }

      $attributes[$k] = [
        'type' => explode(',', $v[self::WEBFORM_FIELD])[0] === '1',
        self::FIELD_MAPPING => isset($v[self::FIELD_MAPPING]) ? $v[self::FIELD_MAPPING] : '',
        self::WEBFORM_FIELD => !$v[self::CUSTOM_CHECK] ? explode(',', $v[self::WEBFORM_FIELD])[1] : '',
        self::CUSTOM_CHECK => $v[self::CUSTOM_CHECK],
        self::CUSTOM_VALUE => $v[self::CUSTOM_CHECK] ? $v[self::CUSTOM_VALUE] : '',
      ];
    }

    $this->entity->set('elements', $attributes);
    $status = $this->entity->save();
    $this->entity->statusMessage($status);
    $form_state->setRedirect('entity.webform_content_creator.collection');
  }

  /**
   * Helper function to check whether a Webform content creator entity exists.
   *
   * @param mixed $id
   *   Entity id.
   *
   * @return bool
   *   True if entity already exists.
   */
  public function exist($id) {
    return WebformContentCreatorUtilities::existsWebformContentCreatorEntity($id);
  }

}
