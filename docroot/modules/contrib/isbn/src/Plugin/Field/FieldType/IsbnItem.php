<?php

namespace Drupal\isbn\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'isbn' field type.
 *
 * @FieldType(
 *   id = "isbn",
 *   label = @Translation("ISBN Field"),
 *   module = "isbn",
 *   description = @Translation("Add ability to insert an ISBN field."),
 *   default_widget = "isbn_widget",
 *   default_formatter = "isbn_default",
 *   constraints = {"IsbnValidation" = {}}
 * )
 */
class IsbnItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return empty($value);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->addConstraint('IsbnValidation')
      ->setLabel(t('ISBN value'));

    return $properties;
  }

  public function preSave() {
    parent::preSave();
    $isbn_tools = \Drupal::service("isbn.isbn_service");
    $clean_value = $isbn_tools->cleanup($this->getString());
    $this->setValue($clean_value);
  }

}
