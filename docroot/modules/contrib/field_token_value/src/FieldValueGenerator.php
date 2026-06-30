<?php

namespace Drupal\field_token_value;

use Drupal\Core\Utility\Token;
use Drupal\Core\Entity\EntityInterface;
use Drupal\token\TokenEntityMapperInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Field\FieldTypePluginManagerInterface;

/**
 * Service to populate the field value for all Field Token Value fields.
 */
class FieldValueGenerator {

  /**
   * The Drupal entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The Drupal field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * The Drupal token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The token entity mapper service.
   *
   * @var \Drupal\token\TokenEntityMapperInterface
   */
  protected $tokenEntityMapper;

  /**
   * The Drupal messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager
   *   The field type plugin manager.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\token\TokenEntityMapperInterface $token_entity_mapper
   *   The token entity mapper service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_plugin_manager, Token $token, TokenEntityMapperInterface $token_entity_mapper, MessengerInterface $messenger) {
    $this->entityFieldManager = $entity_field_manager;
    $this->fieldTypePluginManager = $field_type_plugin_manager;
    $this->token = $token;
    $this->tokenEntityMapper = $token_entity_mapper;
    $this->messenger = $messenger;
  }

  /**
   * Generates all field values for the given entity.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity to generate the field values for.
   */
  public function generateFieldValueForEntity(EntityInterface $entity) {
    if (!($entity instanceof FieldableEntityInterface)) {
      return;
    }

    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions */
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
    foreach ($field_definitions as $field_id => $field_definition) {
      $field_provider = $this->fieldTypePluginManager->getDefinition($field_definition->getType())['provider'];
      if ($field_provider != 'field_token_value') {
        continue;
      }
      try {
        $settings = $field_definition->getSettings();
        $new_field_value = $this->generateFieldValue($entity, $settings);
        $entity->set($field_id, $new_field_value);
      }
      catch (\Exception $e) {
        \Drupal\Component\Utility\DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '10.1.0', fn() => \Drupal\Core\Utility\Error::logException(\Drupal::logger('field_token_value'), $e), fn() => watchdog_exception('field_token_value', $e));
        $warning_message = new TranslatableMarkup('There was an error generating the field value for the field %field.', [
          '%field' => $field_id,
        ]);
        $this->messenger->addWarning($warning_message);
      }
    }

  }

  /**
   * Generates the field value.
   *
   * Performs the token replacement in the configured field value and returns
   * the resulting string.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the field is attached to.
   * @param array $settings
   *   The field settings.
   *
   * @return string
   *   The field value with the tokens replaced.
   */
  public function generateFieldValue(EntityInterface $entity, array $settings) {
    $entity_type = $entity->getEntityTypeId();
    $token_type = $this->tokenEntityMapper->getTokenTypeForEntityType($entity_type);

    // Replace the tokens.
    $value = $this->token->replace($settings['field_value'],
      [$token_type => $entity],
      ['clear' => $settings['remove_empty']]
    );

    return $value;
  }

}
