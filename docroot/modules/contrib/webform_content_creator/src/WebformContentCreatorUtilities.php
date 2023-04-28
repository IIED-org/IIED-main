<?php

namespace Drupal\webform_content_creator;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides useful functions required in Webform content creator module.
 */
class WebformContentCreatorUtilities {

  const WEBFORM = 'webform';

  const WEBFORM_SUBMISSION = 'webform_submission';

  const ENTITY_TYPE_MANAGER = 'entity_type.manager';

  const ENTITY_MANAGER = 'entity_field.manager';

  const CONTENT_BASIC_FIELDS = ['body', 'status', 'uid'];

  /**
   * Function to check whether an Webform content creator entity exists.
   *
   * @param string $id
   *   Webform Content Creator id.
   *
   * @return bool
   *   True, if the entity already exists.
   */
  public static function existsWebformContentCreatorEntity($id) {
    $entity = \Drupal::entityQuery('webform_content_creator')
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

  /**
   * Contructs a tree with webform elements which can be used in Selects.
   *
   * @param array $elements
   *   Webform elements.
   *
   * @return array
   *   Tree with webform elements
   */
  private static function buildTree(array $elements) {
    $elementsDefinitions = \Drupal::service('plugin.manager.webform.element')->getDefinitions();
    $layoutElements = [
      'webform_wizard_page',
      'container',
      'details',
      'fieldset',
      'webform_flexbox',
      'webform_card'
    ];

    $result = [];
    $webformFieldIds = array_keys($elements);
    // Default value, only used if there are no wizard pages in webform.
    $wizardPage = t('Webform elements');
    // Check which element is the first wizard page (in case it exists)
    $flag = 0;
    $aux = [];
    foreach ($webformFieldIds as $v) {
      if ($v === 'actions') {
        continue;
      }
      $title = 'Section';
      if (isset($elements[$v]['#title'])) {
        $title = $elements[$v]['#title'];
      }
      else {
        if (isset($elements[$v]['#markup'])) {
          $title = $elements[$v]['#markup'];
        }
      }

      if (in_array($elements[$v]["#type"], $layoutElements, TRUE)) {
        if ($elements[$v]["#webform_parent_key"] !== '') {
          continue;
        }
        // Executes only for the first wizard page (first optgroup in select)
        if ($flag === 0) {
          $wizardPage = html_entity_decode($title);
          unset($aux);
          $flag++;
          continue;
        }
        if (!empty($aux)) {
          foreach ($aux as $k2 => $v2) {
            $result[$wizardPage][$k2] = $v2;
          }
        }
        $wizardPage = html_entity_decode($title);
        unset($aux);
      }
      // Check if element has not parents.
      elseif ($elements[$v]["#webform_parent_key"] === '') {
        $result['0,' . $v] = html_entity_decode($title) . ' (' . $v . ') - ' . $elementsDefinitions[$elements[$v]["#type"]]['label'];
      }
      // Skip webform sections (not shown in selects)
      elseif ($elements[$v]["#type"] !== "webform_section") {
        $aux['0,' . $v] = html_entity_decode($title) . ' (' . $v . ') - ' . $elementsDefinitions[$elements[$v]["#type"]]['label'];
      }
    }
    // Organize webform elements as a tree (wizard pages as optgroups)
    foreach ($aux as $k2 => $v2) {
      $result[$wizardPage][$k2] = $v2;
    }
    return $result;
  }

  /**
   * Get webform elements and properties structured as a tree.
   *
   * @param string $webform_id
   *   Webform id.
   *
   * @return array
   *   Tree with webform elements and basic attributes.
   */
  public static function getWebformElements($webform_id) {
    $webform = \Drupal::entityTypeManager()->getStorage(self::WEBFORM)->load($webform_id);
    $options = [];
    $submission_storage = \Drupal::entityTypeManager()->getStorage(self::WEBFORM_SUBMISSION);
    $field_definitions = $submission_storage->checkFieldDefinitionAccess($webform, $submission_storage->getFieldDefinitions());
    // Basic webform properties (sid, token, serial number ..)
    foreach ($field_definitions as $key => $field_definition) {
      if (isset($field_definition['type']) && !empty($field_definition['type'])) {
        $options['1,' . $key] = $field_definition['title'] . ' (' . $key . ') - ' . $field_definition['type'];
      }
    }
    // Webform elements.
    $elements = $webform->getElementsInitializedAndFlattened();
    // Webform elements organized in a structured tree.
    $webformOptions = self::buildTree($elements);
    // Join with basic webform properties.
    $webformOptions[t('Webform properties')->render()] = $options;
    return $webformOptions;
  }

  /**
   * Return array with all webform elements types.
   *
   * @param mixed $webform_id
   *   Webform id.
   *
   * @return array
   *   Webform basic attributes and element types
   */
  public static function getWebformElementsTypes($webform_id) {
    if (!isset($webform_id) || empty($webform_id)) {
      return NULL;
    }

    // Get webform entity.
    $webform = \Drupal::entityTypeManager()->getStorage(self::WEBFORM)->load($webform_id);
    if (empty($webform)) {
      return NULL;
    }

    // Get webform submission storage.
    $submissionStorage = \Drupal::entityTypeManager()->getStorage(self::WEBFORM_SUBMISSION);
    $submissionStorageDefinitions = $submissionStorage->getFieldDefinitions();
    if (empty($submissionStorageDefinitions)) {
      return NULL;
    }

    // Get webform basic attributes definitions.
    $fieldDefinitions = $submissionStorage->checkFieldDefinitionAccess($webform, $submissionStorageDefinitions);
    if (empty($fieldDefinitions)) {
      return NULL;
    }

    // Get webform elements and merge with the webform basic attributes.
    $elements = $webform->getElementsInitializedAndFlattened();
    if (is_array($elements)) {
      $webformFieldIds = array_keys($elements);
      foreach ($webformFieldIds as $v) {
        if (!isset($elements[$v]) || empty($elements[$v])) {
          continue;
        }
        $fieldDefinitions[$v] = $elements[$v]['#type'];
      }
    }
    return $fieldDefinitions;
  }

  /**
   * Return the bundle fields.
   *
   * @param string $entity_type_id
   *   Entity type id.
   * @param mixed $bundle_id
   *   Bundle id.
   *
   * @return array
   *   Bundle fields
   */
  public static function bundleFields($entity_type_id, $bundle_id) {
    $entityManager = \Drupal::service(self::ENTITY_MANAGER);
    $fields = [];

    if (!empty($bundle_id)) {
      $fields = $entityManager->getFieldDefinitions($entity_type_id, $bundle_id);
    }
    return $fields;
  }

  /**
   * Get bundle fields, except the basic fields.
   *
   * @param string $entity_type_id
   *   Entity type id.
   * @param mixed $bundle_id
   *   Bundle id.
   *
   * @return array
   *   Associative array bundle fields
   */
  public static function getBundleIds($entity_type_id, $bundle_id) {
    $bundleFields = self::bundleFields($entity_type_id, $bundle_id);
    return array_keys($bundleFields);
  }

  /**
   * Get all webform ids.
   *
   * @return array
   *   Array with all webform ids.
   */
  public static function getAllWebformIds() {
    $ids = \Drupal::entityTypeManager()->getStorage(self::WEBFORM)->getQuery()->condition('template', FALSE)->execute();
    return $ids;
  }

  /**
   * Get all webform entities.
   *
   * @return array
   *   All webform entities.
   */
  public static function getAllWebforms() {
    $ids = self::getAllWebformIds();
    $webforms = \Drupal::entityTypeManager()->getStorage(self::WEBFORM)->loadMultiple(array_keys($ids));
    return $webforms;
  }

  /**
   * Get an associative array with webform ids and respective labels.
   *
   * @return array
   *   Associative array with webform ids and labels.
   */
  public static function getFormattedWebforms() {
    $webforms = self::getAllWebforms();
    $webforms_formatted = [];
    foreach ($webforms as $k => $v) {
      $category = $v->get('category');
      if (empty($category)) {
        $webforms_formatted[$k] = $v->label();
      }
      else {
        $webforms_formatted[$category][$k] = $v->label();
      }
    }

    return $webforms_formatted;
  }

  /**
   * Get an associative array with encryption profiles and respective labels.
   *
   * @return array
   *   Associative array with encryption profiles ids and labels.
   */
  public static function getFormattedEncryptionProfiles() {
    $encryption_profiles = [];
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('encrypt')) {
      $encryption_profiles = \Drupal::service(self::ENTITY_TYPE_MANAGER)->getStorage('encryption_profile')->loadMultiple();
    }
    $encryption_profiles_formatted = [];
    foreach ($encryption_profiles as $k => $v) {
      $encryption_profiles_formatted[$k] = $v->label();
    }
    return $encryption_profiles_formatted;
  }

  /**
   * Get decrypted value.
   *
   * @param string $value
   *   Encrypted value.
   * @param string $encryption_profile
   *   Encryption profile.
   *
   * @return string
   *   Decrypted value
   */
  public static function getDecryptedValue($value, $encryption_profile) {
    if (empty($value) || empty($encryption_profile)) {
      return '';
    }
    $dec_value = FALSE;
    if (\Drupal::service('module_handler')->moduleExists('encrypt')) {
      $dec_value = \Drupal::service('encryption')->decrypt($value, $encryption_profile);
    }
    if ($dec_value === FALSE) {
      $dec_value = $value;
    }
    return $dec_value;
  }

  /**
   * Get decrypted values inside text with tokens.
   *
   * @param string $value
   *   String with tokens.
   * @param string $encryption_profile
   *   Encryption profile.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Webform submission.
   *
   * @return string
   *   Token value.
   */
  public static function getDecryptedTokenValue($value, $encryption_profile, WebformSubmissionInterface $webform_submission) {
    if (empty($value) || empty($webform_submission)) {
      return '';
    }
    // Get tokens in string.
    $tokens = \Drupal::token()->scan($value);
    $token_keys = [];
    $token_values = [];
    if (empty($tokens)) {
      return $value;
    }
    foreach ($tokens as $types) {
      foreach ($types as $val) {
        $token_value = \Drupal::token()->replace($val, [self::WEBFORM_SUBMISSION => $webform_submission], ['clear' => TRUE]);
        if (!empty($encryption_profile)) {
          // Decrypt single token value.
          $dec_token_value = self::getDecryptedValue($token_value, $encryption_profile);
        }
        else {
          $dec_token_value = $token_value;
        }
        $token_keys[] = $val;
        $token_values[] = $dec_token_value;
      }
    }
    if (empty($token_values)) {
      return $value;
    }
    // Replace all token values in string.
    $dec_value = str_replace($token_keys, $token_values, $value);
    return $dec_value;
  }

}
