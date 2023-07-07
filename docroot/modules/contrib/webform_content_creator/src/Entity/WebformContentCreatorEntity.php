<?php

namespace Drupal\webform_content_creator\Entity;

use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\webform_content_creator\WebformContentCreatorInterface;
use Drupal\webform_content_creator\WebformContentCreatorUtilities;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Component\Utility\Html;

/**
 * Defines the Webform Content Creator entity.
 *
 * @ConfigEntityType(
 *   id = "webform_content_creator",
 *   label = @Translation("Webform Content Creator"),
 *   handlers = {
 *     "list_builder" = "Drupal\webform_content_creator\Controller\WebformContentCreatorListBuilder",
 *     "form" = {
 *       "add" = "Drupal\webform_content_creator\Form\WebformContentCreatorForm",
 *       "edit" = "Drupal\webform_content_creator\Form\WebformContentCreatorForm",
 *       "delete" = "Drupal\webform_content_creator\Form\WebformContentCreatorDeleteForm",
 *       "manage_fields" = "Drupal\webform_content_creator\Form\WebformContentCreatorManageFieldsForm",
 *     }
 *   },
 *   config_prefix = "webform_content_creator",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "title" = "title",
 *     "webform" = "webform",
 *     "content_type" = "content_type",
 *     "target_entity_type" = "target_entity_type",
 *     "target_bundle" = "target_bundle",
 *   },
 *   links = {
 *     "manage-fields-form" = "/admin/config/webform_content_creator/manage/{webform_content_creator}/fields",
 *     "edit-form" = "/admin/config/webform_content_creator/{webform_content_creator}",
 *     "delete-form" = "/admin/config/webform_content_creator/{webform_content_creator}/delete",
 *   },
 *   config_export = {
 *     "id",
 *     "title",
 *     "webform",
 *     "field_title",
 *     "use_encrypt",
 *     "encryption_profile",
 *     "sync_content",
 *     "sync_content_delete",
 *     "sync_content_node_field",
 *     "sync_content_field",
 *     "elements",
 *     "content_type",
 *     "target_entity_type",
 *     "target_bundle",
 *   }
 * )
 */
class WebformContentCreatorEntity extends ConfigEntityBase implements WebformContentCreatorInterface {

  use StringTranslationTrait, MessengerTrait;

  /**
   * Webform content creator entity id.
   *
   * @var string
   */
  protected $id;

  /**
   * Webform content creator entity title.
   *
   * @var string
   */
  protected $title;

  /**
   * Content title.
   *
   * @var string
   */
  protected $field_title;

  /**
   * Webform machine name.
   *
   * @var string
   */
  protected $webform;

  /**
   * Target entity type machine name.
   *
   * @var string
   */
  protected $target_entity_type;

  /**
   * Target bundle machine name.
   *
   * @var string
   */
  protected $target_bundle;

  /**
   * Mapping between webform submission values and content field values.
   *
   * @var array
   */
  protected $elements;

  /**
   * Use encryption.
   *
   * @var bool
   */
  protected $use_encrypt;

  /**
   * Encryption profile.
   *
   * @var string
   */
  protected $encryption_profile;

  /**
   * Returns the entity title.
   *
   * @return string
   *   The entity title.
   */
  public function getTitle() {
    return $this->get('title');
  }

  /**
   * Sets the entity title.
   *
   * @param string $title
   *   Content title.
   *
   * @return $this
   *   The Webform Content Creator entity.
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * Returns the target entity type id.
   *
   * @return string
   *   The target entity type id.
   */
  public function getEntityTypeValue() {
    $result = $this->get('target_entity_type');
    if (empty($result)) {
      $result = 'node';
    }
    return $result;
  }

  /**
   * Sets the target entity type id.
   *
   * @param string $entityType
   *   Target entity type id.
   *
   * @return $this
   *   The Webform Content Creator entity.
   */
  public function setEntityTypeValue($entityType) {
    $this->set('target_entity_type', $entityType);
    return $this;
  }

  /**
   * Returns the target bundle id.
   *
   * @return string
   *   The target bundle id.
   */
  public function getBundleValue() {
    $result = $this->get('target_bundle');
    if (empty($result)) {
      $result = $this->get('content_type');
    }
    return $result;
  }

  /**
   * Sets the target bundle id.
   *
   * @param string $bundle
   *   Target bundle id.
   *
   * @return $this
   *   The Webform Content Creator entity.
   */
  public function setBundleValue($bundle) {
    $this->set('target_bundle', $bundle);
    return $this;
  }

  /**
   * Returns the entity webform id.
   *
   * @return string
   *   The entity webform.
   */
  public function getWebform() {
    return $this->get('webform');
  }

  /**
   * Sets the entity webform id.
   *
   * @param string $webform
   *   Webform id.
   *
   * @return $this
   *   The Webform Content Creator entity.
   */
  public function setWebform($webform) {
    $this->set('webform', $webform);
    return $this;
  }

  /**
   * Returns the entity attributes as an associative array.
   *
   * @return array
   *   The entity attributes mapping.
   */
  public function getAttributes() {
    return $this->get(WebformContentCreatorInterface::ELEMENTS);
  }

  /**
   * Check if synchronization between content entities and webform submissions is used.
   *
   * @return bool
   *   true, when the synchronization is used. Otherwise, returns false.
   */
  public function getSyncEditContentCheck() {
    return $this->get(WebformContentCreatorInterface::SYNC_CONTENT);
  }

  /**
   * Check if synchronization is used in deletion.
   *
   * @return bool
   *   true, when the synchronization is used. Otherwise, returns false.
   */
  public function getSyncDeleteContentCheck() {
    return $this->get(WebformContentCreatorInterface::SYNC_CONTENT_DELETE);
  }

  /**
   * Get content field in which the webform submission id will be stored.
   *
   * @return string
   *   Field machine name.
   */
  public function getSyncContentField() {
    return $this->get(WebformContentCreatorInterface::SYNC_CONTENT_FIELD);
  }

  /**
   * Returns the encryption method.
   *
   * @return bool
   *   true, when an encryption profile is used. Otherwise, returns false.
   */
  public function getEncryptionCheck() {
    return $this->get(WebformContentCreatorInterface::USE_ENCRYPT);
  }

  /**
   * Returns the encryption profile.
   *
   * @return string
   *   The encryption profile name.
   */
  public function getEncryptionProfile() {
    return $this->get(WebformContentCreatorInterface::ENCRYPTION_PROFILE);
  }

  /**
   * Get encryption profile name.
   *
   * @return string
   *   Encryption profile name.
   */
  private function getProfileName() {
    $encryption_profile = '';
    $use_encrypt = $this->get(WebformContentCreatorInterface::USE_ENCRYPT);
    if ($use_encrypt) {
      $encryption_profile = \Drupal::service('entity_type.manager')->getStorage(WebformContentCreatorInterface::ENCRYPTION_PROFILE)->load($this->getEncryptionProfile());
    }

    return $encryption_profile;
  }

  /**
   * Get webform submission value.
   *
   * @param string $value
   *   Webform submission value.
   * @param string $profile
   *   (Optional) Encryption profile name.
   *
   * @return string
   *   Encryption profile used to encrypt/decrypt $value
   */
  private function getWebformSubmissionValue($value, $profile = '') {
    if ($this->getEncryptionCheck()) {
      $result = WebformContentCreatorUtilities::getDecryptedValue($value, $profile);
    }
    else {
      $result = $value;
    }
    return $result;
  }

  /**
   * Use a single mapping to set a Content field value.
   *
   * @param \Drupal\Core\Entity\EntityInterface $initial_content
   *   Content entity being mapped with a webform submission.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Webform submission entity.
   * @param array $fields
   *   Content entity fields.
   * @param array $data
   *   Webform submission data.
   * @param string $encryption_profile
   *   Encryption profile used in Webform encrypt module.
   * @param string $field_id
   *   Content field id.
   * @param array $mapping
   *   Single mapping between content entity and webform submission.
   * @param array $attributes
   *   All mapping values between Content entity and Webform submission values.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Created content entity.
   */
  private function mapContentField(EntityInterface $initial_content, WebformSubmissionInterface $webform_submission, array $fields = [], array $data = [], $encryption_profile = '', $field_id = '', array $mapping = [], array $attributes = []) {
    $content = $initial_content;

    if (!$content->hasField($field_id) || !is_array($mapping)) {
      return $content;
    }

    // Get the field mapping plugin.
    $field_mapping = \Drupal::service('plugin.manager.webform_content_creator.field_mapping')->getPlugin($attributes[$field_id][WebformContentCreatorInterface::FIELD_MAPPING]);
    $values = [];
    $webform_element = [];

    // If the custom check functionality is active then we do need to evaluate
    // webform fields.
    if ($attributes[$field_id][WebformContentCreatorInterface::CUSTOM_CHECK]) {
      $field_value = WebformContentCreatorUtilities::getDecryptedTokenValue($mapping[WebformContentCreatorInterface::CUSTOM_VALUE], $encryption_profile, $webform_submission);
    }
    else {
      if (!$attributes[$field_id][WebformContentCreatorInterface::TYPE]) {
        $field_value = $this->getWebformSubmissionValue($data[$mapping[WebformContentCreatorInterface::WEBFORM_FIELD]], $encryption_profile);
        if ($fields[$field_id]->getType() === 'entity_reference' && (!is_array($field_value) && intval($field_value) === 0)) {
          $content->set($field_id, []);
        }
      }
      else {
        $field_object = $webform_submission->{$mapping[WebformContentCreatorInterface::WEBFORM_FIELD]};
        if ($field_object instanceof EntityReferenceFieldItemList) {
          $field_value = $webform_submission->{$mapping[WebformContentCreatorInterface::WEBFORM_FIELD]}->getValue()[0]['target_id'];
        }
        else {
          $field_value = $webform_submission->{$mapping[WebformContentCreatorInterface::WEBFORM_FIELD]}->value;
        }

      }
    }
    $values[$field_id] = $field_value;

    // Map the field type using the selected field mapping.
    $field_value = $field_mapping->mapEntityField($content, $webform_element, $fields[$field_id], $values, $mapping);

    return $content;
  }

  /**
   * Create content entity from webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Webform submission.
   */
  public function createContent(WebformSubmissionInterface $webform_submission) {
    $entity_type_id = $this->getEntityTypeValue();
    $bundle_id = $this->getBundleValue();

    $fields = WebformContentCreatorUtilities::bundleFields($entity_type_id, $bundle_id);
    if (empty($fields)) {
      return FALSE;
    }

    // Get webform submission data.
    $data = $webform_submission->getData();
    if (empty($data)) {
      return 0;
    }

    $encryptionProfile = $this->getProfileName();

    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);

    // Create new content.
    $content = \Drupal::entityTypeManager()->getStorage($entity_type_id)->create([
      $entity_type->getKey('bundle') => $this->getBundleValue(),
    ]);

    // Set content fields values.
    $attributes = $this->get(WebformContentCreatorInterface::ELEMENTS);

    if (!$this->existsBundle()) {
      return FALSE;
    }

    foreach ($attributes as $k2 => $v2) {
      $content = $this->mapContentField($content, $webform_submission, $fields, $data, $encryptionProfile, $k2, $v2, $attributes);
    }

    $result = FALSE;

    // Save content.
    try {
      $result = $content->save();
    }
    catch (\Exception $e) {
      \Drupal::logger(WebformContentCreatorInterface::WEBFORM_CONTENT_CREATOR)->error($this->t('A problem occurred when creating a new content.'));
      \Drupal::logger(WebformContentCreatorInterface::WEBFORM_CONTENT_CREATOR)->error($e->getMessage());
    }
    return $result;
  }

  /**
   * Update content from webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Webform submission.
   * @param string $op
   *   Operation.
   *
   * @return bool
   *   True, if succeeded. Otherwise, return false.
   */
  public function updateContent(WebformSubmissionInterface $webform_submission, $op = 'edit') {
    if (empty($this->getSyncContentField())) {
      return FALSE;
    }

    $entity_type_id = $this->getEntityTypeValue();
    $bundle_id = $this->getBundleValue();

    $fields = WebformContentCreatorUtilities::bundleFields($entity_type_id, $bundle_id);
    if (empty($fields)) {
      return FALSE;
    }

    if (!array_key_exists($this->getSyncContentField(), $fields)) {
      return FALSE;
    }

    // Get webform submission data.
    $data = $webform_submission->getData();
    if (empty($data)) {
      return FALSE;
    }

    $encryptionProfile = $this->getProfileName();

    // Get contents created from this webform submission.
    $contents = \Drupal::entityTypeManager()
      ->getStorage($entity_type_id)
      ->loadByProperties([$this->getSyncContentField() => $webform_submission->id()]);

    // Use only first result, if exists.
    if (!($content = reset($contents))) {
      return FALSE;
    }

    if ($op === 'delete' && !empty($this->getSyncDeleteContentCheck())) {
      $result = $content->delete();
      return $result;
    }

    if (empty($this->getSyncEditContentCheck())) {
      return FALSE;
    }

    // Set content fields values.
    $attributes = $this->get(WebformContentCreatorInterface::ELEMENTS);

    foreach ($attributes as $k2 => $v2) {
      $content = $this->mapContentField($content, $webform_submission, $fields, $data, $encryptionProfile, $k2, $v2, $attributes);
    }

    $result = FALSE;

    // Save content.
    try {
      $result = $content->save();
    }
    catch (\Exception $e) {
      \Drupal::logger(WebformContentCreatorInterface::WEBFORM_CONTENT_CREATOR)->error($this->t('A problem occurred while updating content.'));
      \Drupal::logger(WebformContentCreatorInterface::WEBFORM_CONTENT_CREATOR)->error($e->getMessage());
    }

    return $result;

  }

  /**
   * Check if the entity type exists.
   *
   * @return bool
   *   True, if entity type exists. Otherwise, returns false.
   */
  public function existsEntityType() {
    // Get entity type id.
    $entity_type_id = $this->getEntityTypeValue();

    $entity_keys = array_keys(\Drupal::entityTypeManager()->getDefinitions());
    return in_array($entity_type_id, $entity_keys);
  }

  /**
   * Check if the bundle exists.
   *
   * @return bool
   *   True, if the bundle exists. Otherwise, returns false.
   */
  public function existsBundle() {
    // Get entity type id.
    $entity_type_id = $this->getEntityTypeValue();

    // Get bundle id.
    $bundle_id = $this->getBundleValue();

    // Get bundles of entity type being used.
    $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type_id);
    $bundles = array_keys($bundles);
    return in_array($bundle_id, $bundles);
  }

  /**
   * Check if the target entity type id is equal to the configured entity type.
   *
   * @param string $e
   *   Target entity type id.
   *
   * @return bool
   *   True, if the parameter is equal to the target entity type id of Webform
   *   content creator entity. Otherwise, returns false.
   */
  public function equalsEntityType($e) {
    return $e === $this->getEntityTypeValue();
  }

  /**
   * Check if the target bundle id is equal to the configured bundle.
   *
   * @param string $bundle
   *   Target bundle id.
   *
   * @return bool
   *   True, if the parameter is equal to the target bundle id of Webform
   *   content creator entity. Otherwise, returns false.
   */
  public function equalsBundle($bundle) {
    return $bundle === $this->getBundleValue();
  }

  /**
   * Check if the webform id is equal to the configured webform id.
   *
   * @param string $webform
   *   Webform id.
   *
   * @return bool
   *   True, if the parameter is equal to the webform id of Webform
   *   content creator entity. Otherwise, returns false.
   */
  public function equalsWebform($webform) {
    return $webform === $this->getWebform();
  }

  /**
   * Show a message accordingly to status, after creating/updating an entity.
   *
   * @param int $status
   *   Status int, returned after creating/updating an entity.
   */
  public function statusMessage($status) {
    if ($status) {
      $this->messenger()->addMessage($this->t('Saved the %label entity.', ['%label' => $this->getTitle()]));
    }
    else {
      $this->messenger()->addMessage($this->t('The %label entity was not saved.', ['%label' => $this->getTitle()]));
    }
  }

  /**
   * Check if field maximum size is exceeded.
   *
   * @param array $fields
   *   Content type fields.
   * @param string $k
   *   Field machine name.
   * @param string $decValue
   *   Decrypted value.
   *
   * @return int
   *   1 if maximum size is exceeded, otherwise return 0.
   */
  public function checkMaxFieldSizeExceeded(array $fields, $k, $decValue = "") {
    if (!array_key_exists($k, $fields) || empty($fields[$k])) {
      return 0;
    }
    $fieldSettings = $fields[$k]->getSettings();
    if (empty($fieldSettings) || !array_key_exists('max_length', $fieldSettings)) {
      return 0;
    }

    $maxLength = $fieldSettings['max_length'];
    if (empty($maxLength)) {
      return 0;
    }
    if ($maxLength < strlen($decValue)) {
      \Drupal::logger(WebformContentCreatorInterface::WEBFORM_CONTENT_CREATOR)->notice($this->t('Problem: Field max length exceeded (truncated).'));
      return $maxLength;
    }
    return strlen($decValue);
  }

  /**
   * Convert timestamp value according to field type.
   *
   * @param int $field_value
   *   Original datetime value.
   * @param array $fields
   *   Bundle fields.
   * @param int $field_id
   *   Field machine name id.
   *
   * @return Timestamp
   *   Converted value.
   */
  public function convertTimestamp($field_value, array $fields, $field_id) {
    $datetime = new DrupalDateTime($field_value, 'UTC');
    $date_type = $fields[$field_id]->getSettings()['datetime_type'];
    if ($date_type === 'datetime') {
      $result = \Drupal::service('date.formatter')->format(
        $datetime->getTimestamp(), 'custom',
        DateTimeItemInterface::DATETIME_STORAGE_FORMAT, 'UTC'
      );
    }
    else {
      $result = \Drupal::service('date.formatter')->format(
        $datetime->getTimestamp(), 'custom',
        DateTimeItemInterface::DATE_STORAGE_FORMAT, 'UTC'
      );
    }

    return $result;
  }

}
