<?php

namespace Drupal\webform_content_creator;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides an interface defining an Webform content creator entity.
 */
interface WebformContentCreatorInterface extends ConfigEntityInterface {
  const WEBFORM = 'webform';

  const WEBFORM_CONTENT_CREATOR = 'webform_content_creator';

  const FIELD_TITLE = 'field_title';

  const WEBFORM_FIELD = 'webform_field';

  const FIELD_MAPPING = 'mapping';

  const CUSTOM_CHECK = 'custom_check';

  const CUSTOM_VALUE = 'custom_value';

  const ELEMENTS = 'elements';

  const TYPE = 'type';

  const SYNC_CONTENT = 'sync_content';

  const SYNC_CONTENT_DELETE = 'sync_content_delete';

  const SYNC_CONTENT_FIELD = 'sync_content_field';

  const USE_ENCRYPT = 'use_encrypt';

  const ENCRYPTION_PROFILE = 'encryption_profile';

  /**
   * Returns the entity title.
   *
   * @return string
   *   The entity title.
   */
  public function getTitle();

  /**
   * Sets the entity title.
   *
   * @param string $title
   *   Content title.
   *
   * @return $this
   *   The Webform Content Creator entity.
   */
  public function setTitle($title);

  /**
   * Returns the target entity type id.
   *
   * @return string
   *   The target entity type id.
   */
  public function getEntityTypeValue();

  /**
   * Sets the target entity type id.
   *
   * @param string $entityType
   *   Target entity type id.
   *
   * @return $this
   *   The Webform Content Creator entity.
   */
  public function setEntityTypeValue($entityType);

  /**
   * Returns the target bundle id.
   *
   * @return string
   *   The target bundle id.
   */
  public function getBundleValue();

  /**
   * Sets the target bundle id.
   *
   * @param string $bundle
   *   Target bundle id.
   *
   * @return $this
   *   The Webform Content Creator entity.
   */
  public function setBundleValue($bundle);

  /**
   * Returns the entity webform id.
   *
   * @return string
   *   The entity webform.
   */
  public function getWebform();

  /**
   * Sets the entity webform id.
   *
   * @param string $webform
   *   Webform id.
   *
   * @return $this
   *   The Webform Content Creator entity.
   */
  public function setWebform($webform);

  /**
   * Returns the entity attributes as an associative array.
   *
   * @return array
   *   The entity attributes mapping.
   */
  public function getAttributes();

  /**
   * Check if synchronization between content entities and webform submissions is used.
   *
   * @return bool
   *   true, when the synchronization is used. Otherwise, returns false.
   */
  public function getSyncEditContentCheck();

  /**
   * Check if synchronization is used in deletion.
   *
   * @return bool
   *   true, when the synchronization is used. Otherwise, returns false.
   */
  public function getSyncDeleteContentCheck();

  /**
   * Get content field in which the webform submission id will be stored.
   *
   * @return string
   *   Field machine name.
   */
  public function getSyncContentField();

  /**
   * Returns the encryption method.
   *
   * @return bool
   *   true, when an encryption profile is used. Otherwise, returns false.
   */
  public function getEncryptionCheck();

  /**
   * Returns the encryption profile.
   *
   * @return string
   *   The encryption profile name.
   */
  public function getEncryptionProfile();

  /**
   * Check if the target entity type exists.
   *
   * @return bool
   *   True, if the target entity type exists. Otherwise, returns false.
   */
  public function existsEntityType();

  /**
   * Check if the target bundle exists.
   *
   * @return bool
   *   True, if the target bundle exists. Otherwise, returns false.
   */
  public function existsBundle();

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
  public function equalsEntityType($e);

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
  public function equalsBundle($bundle);

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
  public function equalsWebform($webform);

  /**
   * Show a message accordingly to status, after creating/updating an entity.
   *
   * @param int $status
   *   Status int, returned after creating/updating an entity.
   */
  public function statusMessage($status);

  /**
   * Create content from webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Webform submission.
   */
  public function createContent(WebformSubmissionInterface $webform_submission);

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
  public function updateContent(WebformSubmissionInterface $webform_submission, $op);

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
  public function checkMaxFieldSizeExceeded(array $fields, $k, $decValue);

}
