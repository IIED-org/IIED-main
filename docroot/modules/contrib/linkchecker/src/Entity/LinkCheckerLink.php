<?php

namespace Drupal\linkchecker\Entity;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\linkchecker\LinkCheckerLinkInterface;

/**
 * Defines the linkcheckerlink type entity.
 *
 * @ContentEntityType(
 *   id = "linkcheckerlink",
 *   label = @Translation("LinkChecker link type"),
 *   label_singular = @Translation("LinkChecker link type"),
 *   label_plural = @Translation("LinkChecker link types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count LinkChecker link type",
 *     plural = "@count LinkChecker link types",
 *   ),
 *   handlers = {
 *    "storage" = "Drupal\linkchecker\LinkCheckerStorage",
 *    "access" = "Drupal\linkchecker\LinkCheckerLinkAccessControlHandler",
 *    "storage_schema" = "Drupal\linkchecker\LinkCheckerLinkStorageSchema",
 *    "form" = {
 *      "default" = "Drupal\linkchecker\Form\LinkCheckerLinkForm",
 *      "edit" = "Drupal\linkchecker\Form\LinkCheckerLinkForm"
 *    },
 *    "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *    "route_provider" = {
 *      "html" = "Drupal\linkchecker\LinkCheckerLinkRouteProvider"
 *    },
 *    "views_data" = "Drupal\views\EntityViewsData"
 *   },
 *   translatable = FALSE,
 *   base_table = "linkchecker_link",
 *   admin_permission = "administer linkchecker",
 *   entity_keys = {
 *     "id" = "lid",
 *     "published" = "status"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/content/linkcheckerlink/{linkcheckerlink}/edit"
 *   }
 * )
 */
class LinkCheckerLink extends ContentEntityBase implements LinkCheckerLinkInterface {

  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function generateHash($uri) {
    return Crypt::hashBase64($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage_controller) {
    $url = $this->getUrl();
    if (!is_null($url)) {
      $this->setHash(LinkCheckerLink::generateHash($url));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getHash() {
    return $this->get('urlhash')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setHash($hash) {
    $this->set('urlhash', $hash);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return $this->get('url')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setUrl($url) {
    $this->get('url')->value = $url;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestMethod() {
    return $this->get('method')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequestMethod($method) {
    $this->set('method', $method);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusCode() {
    return $this->get('code')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatusCode($code) {
    $this->set('code', $code);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorMessage() {
    return $this->get('error')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setErrorMessage($message) {
    $this->set('error', $message);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFailCount() {
    return $this->get('fail_count')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFailCount($count) {
    $this->set('fail_count', $count);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastCheckTime() {
    return $this->get('last_check')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastCheckTime($time) {
    $this->set('last_check', $time);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentEntity() {
    return $this->get('entity_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setParentEntity(FieldableEntityInterface $entity) {
    $this->get('entity_id')->target_id = $entity->id();
    $this->get('entity_id')->target_type = $entity->getEntityTypeId();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentEntityFieldName() {
    return $this->get('entity_field')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setParentEntityFieldName($fieldName) {
    $this->set('entity_field', $fieldName);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentEntityLangcode() {
    return $this->get('entity_langcode')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setParentEntityLangcode($langcode) {
    $this->set('entity_langcode', $langcode);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isLinkCheckStatus() {
    return $this->isPublished();
  }

  /**
   * {@inheritdoc}
   */
  public function setEnableLinkCheck() {
    return $this->setPublished();
  }

  /**
   * {@inheritdoc}
   */
  public function setDisableLinkCheck() {
    return $this->setUnpublished();
  }

  /**
   * {@inheritdoc}
   */
  public function isExists() {
    return \Drupal::service('linkchecker.extractor')->isLinkExists($this);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    // Standard field for making each link entity unique.
    $fields[$entity_type->getKey('id')]->setLabel(new TranslatableMarkup('Link ID'))
      ->setDescription(new TranslatableMarkup('The ID of the link entity.'));

    $fields[$entity_type->getKey('published')]->setLabel(new TranslatableMarkup('Check link status'));

    // Hash of URL.
    $fields['urlhash'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('URL hash'))
      ->setDescription(new TranslatableMarkup('The indexable hash of the {linkchecker_link}.url.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', 64);

    // URI related to the link.
    $fields['url'] = BaseFieldDefinition::create('uri')
      ->setLabel(new TranslatableMarkup('URL'))
      ->setDescription(new TranslatableMarkup('The full qualified link.'))
      ->setRequired(TRUE);

    // Method related to the link.
    $fields['method'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Method'))
      ->setDescription(new TranslatableMarkup('The method for checking links (HEAD, GET, POST).'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', 4)
      ->setDefaultValue('HEAD');

    // Code related to the link.
    $fields['code'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Status code'))
      ->setDescription(new TranslatableMarkup('HTTP status code from link checking.'));

    // Error related to the link.
    $fields['error'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Error'))
      ->setDescription(new TranslatableMarkup('The error message received from the remote server while doing link checking.'));

    // Fail count: number of failed checks related to the link of the entity.
    $fields['fail_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Fail count'))
      ->setDescription(new TranslatableMarkup('Fail count of unsuccessful link checks. No flapping detection. (Successful = 0, Unsuccessful = fail_count+1).'))
      ->setSetting('unsigned', TRUE)
      ->setDefaultValue(0);

    // Timestamp for last check related to the link.
    $fields['last_check'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Last checked'))
      ->setDescription(new TranslatableMarkup('Timestamp of the last link check.'));

    // Entity id related to the link.
    $fields['entity_id'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(new TranslatableMarkup('Entity id'))
      ->setDescription(new TranslatableMarkup('ID of entity in which link was found.'))
      ->setRequired(TRUE);

    // Entity field related to the link.
    $fields['entity_field'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Entity field'))
      ->setDescription(new TranslatableMarkup('Field of entity in which link was found.'))
      ->setRequired(TRUE);

    // Entity langcode related to the link.
    $fields['entity_langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(new TranslatableMarkup('Entity language'))
      ->setDescription(new TranslatableMarkup('Language of entity in which link was found.'))
      ->setRequired(TRUE);

    return $fields;
  }

}
