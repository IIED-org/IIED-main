<?php

namespace Drupal\media_pdf_thumbnail\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Pdf image entity entity.
 *
 * @ingroup media_pdf_thumbnail
 *
 * @ContentEntityType(
 *   id = "pdf_image_entity",
 *   label = @Translation("Pdf image entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\media_pdf_thumbnail\PdfImageEntityListBuilder",
 *     "views_data" = "Drupal\media_pdf_thumbnail\Entity\PdfImageEntityViewsData",
 *
 *     "form" = {
 *       "delete" = "Drupal\media_pdf_thumbnail\Form\PdfImageEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\media_pdf_thumbnail\PdfImageEntityHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\media_pdf_thumbnail\PdfImageEntityAccessControlHandler",
 *   },
 *
 *   base_table = "pdf_image_entity",
 *   translatable = FALSE,
 *   admin_permission = "administer pdf image entity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/pdf_image_entity/{pdf_image_entity}",
 *     "delete-form" = "/admin/structure/pdf_image_entity/{pdf_image_entity}/delete",
 *     "collection" = "/admin/structure/pdf_image_entity",
 *   },
 *   field_ui_base_route = "pdf_image_entity.settings"
 * )
 */
class PdfImageEntity extends ContentEntityBase implements PdfImageEntityInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')->setLabel(t('Name'))->setDescription(t('The name of the Pdf image entity entity.'))->setSettings([
      'max_length' => 50,
      'text_processing' => 0,
    ])->setDefaultValue('')->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'string',
      'weight' => -4,
    ])->setDisplayOptions('form', [
      'type' => 'string_textfield',
      'weight' => -4,
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE)->setRequired(TRUE);

    $fields['status']->setDescription(t('A boolean indicating whether the Pdf image entity is published.'))->setDisplayOptions('form', [
      'type' => 'boolean_checkbox',
      'weight' => -3,
    ]);

    $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Created'))->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Changed'))->setDescription(t('The time that the entity was last edited.'));

    $fields['referenced_entity_type'] = BaseFieldDefinition::create('string')->setLabel(t('Referenced Entity type'))->setDescription(t('The entity type that is referenced.'));

    $fields['referenced_entity_bundle'] = BaseFieldDefinition::create('string')->setLabel(t('Referenced Entity bundle'))->setDescription(t('The entity bundle that is referenced.'));

    $fields['referenced_entity_id'] = BaseFieldDefinition::create('string')->setLabel(t('Referenced Entity id'))->setDescription(t('The referenced entity.'));

    $fields['referenced_entity_revision_id'] = BaseFieldDefinition::create('string')->setLabel(t('Referenced Entity revision id'))->setDescription(t('The entity revision id that is referenced.'));

    $fields['referenced_entity_lang'] = BaseFieldDefinition::create('string')->setLabel(t('Referenced Entity language'))->setDescription(t('The entity language that is referenced.'));

    $fields['referenced_entity_field'] = BaseFieldDefinition::create('string')->setLabel(t('Referenced Entity pdf field'))->setDescription(t('The entity field that referenced pdf file.'));

    $fields['pdf_file_id'] = BaseFieldDefinition::create('string')->setLabel(t('PDF file id'))->setDescription(t('The id of the pdf file.'));

    $fields['pdf_file_uri'] = BaseFieldDefinition::create('string')->setLabel(t('PDF file uri'))->setDescription(t('The uri of the pdf file.'));

    $fields['pdf_file_page'] = BaseFieldDefinition::create('string')->setLabel(t('PDF page'))->setDescription(t('The page of the pdf file.'));

    $fields['image_file_id'] = BaseFieldDefinition::create('string')->setLabel(t('Image file id'))->setDescription(t('The id of the image file.'));

    $fields['image_file_uri'] = BaseFieldDefinition::create('string')->setLabel(t('Image file uri'))->setDescription(t('The uri of the image file.'));

    $fields['image_format'] = BaseFieldDefinition::create('string')->setLabel(t('Image format'))->setDescription(t('The format of the image file.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

}
