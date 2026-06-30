<?php

namespace Drupal\field_token_value\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Provides the Field Token Value field type.
 *
 * @FieldType(
 *   id = "field_token_value",
 *   module = "field_token_value",
 *   label = @Translation("Field Token Value"),
 *   description = @Translation("Create a field value with the use of tokens."),
 *   default_widget = "field_token_value_default",
 *   default_formatter = "field_token_value_text"
 * )
 */
class FieldTokenValueItem extends FieldItemBase {

  /**
   * The token entity mapper service.
   *
   * @var \Drupal\token\TokenEntityMapperInterface
   */
  protected $tokenEntityMapper;

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'field_value' => '',
      'remove_empty' => TRUE,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'text',
          'size' => 'big',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $entity_type = $this->getEntity()->getEntityTypeId();
    $token_type = $this->getTokenEntityMapper()->getTokenTypeForEntityType($entity_type);

    $element['field_value'] = [
      '#type' => 'textfield',
      '#maxlength' => 1024,
      '#title' => $this->t('Field value'),
      '#description' => $this->t('Enter the value for this field. Tokens are automatically replaced upon saving of the node itself.'),
      '#default_value' => $this->getSetting('field_value'),
      '#element_validate' => ['token_element_validate'],
      '#token_types' => [$token_type],
      '#required' => TRUE,
    ];

    $element['token_help'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => [$token_type],
    ];

    $element['remove_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove empty tokens'),
      '#description' => $this->t('Select this option to remove tokens from the final text if no replacement value can be generated.'),
      '#default_value' => $this->getSetting('remove_empty'),
    ];

    return $element;
  }

  /**
   * Returns the token entity mapper service.
   *
   * @return \Drupal\token\TokenEntityMapperInterface
   *   The token entity mapper service.
   */
  protected function getTokenEntityMapper() {
    if (!$this->tokenEntityMapper) {
      $this->tokenEntityMapper = \Drupal::service('token.entity_mapper');
    }
    return $this->tokenEntityMapper;
  }

}
