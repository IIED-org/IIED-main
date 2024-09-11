<?php

namespace Drupal\media_pdf_thumbnail\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Url;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\media_pdf_thumbnail\Manager\MediaPdfThumbnailImageManager;
use Drupal\media_pdf_thumbnail\Manager\PdfImageEntityQueueManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'image' formatter.
 *
 * @FieldFormatter(
 *   id = "media_pdf_thumbnail_image_field_formatter",
 *   label = @Translation("Media PDF Thumbnail Image"),
 *   field_types = {
 *     "image"
 *   },
 *   quickedit = {
 *     "editor" = "image"
 *   }
 * )
 */
class MediaPdfThumbnailImageFieldFormatter extends ImageFormatter {

  use ImageFieldFormatterElementViewTrait;

  use ImageFieldFormatterSettingsFormTrait;

  const PDF_FILE_FIELD_SETTING = 'pdf_file_field';

  const PDF_PAGE_SETTING = 'pdf_page';

  const DEFAULT_PDF_PAGE_SETTING = 1;

  const IMAGE_FORMAT_SETTINGS = 'image_format';

  const IMAGE_LINK_SETTINGS = 'image_link';

  const IMAGE_STYLE_SETTINGS = 'image_style';

  const DEFAULT_IMAGE_FORMAT_SETTING = 'jpg';

  const IMAGE_LINK_TYPE_SETTING = 'pdf_file';

  const IMAGE_LINK_ATTRIBUTE_DOWNLOAD_SETTING = 'image_link_attributes_download';

  const IMAGE_LINK_ATTRIBUTE_TARGET_SETTING = 'image_link_attributes_target';

  const IMAGE_LINK_ATTRIBUTE_REL_SETTING = 'image_link_attributes_rel';

  const IMAGE_USE_CRON = 'use_cron';

  const MEDIA_BUNDLE_PAGE = '_page';

  const MEDIA_BUNDLE_FIELD = '_field';

  const MEDIA_BUNDLE_ENABLE = '_enable';

  const MEDIA_BUNDLE_LINK = '_link';

  const MEDIA_BUNDLE_IMAGE_STYLE = '_image_style';

  const MEDIA_BUNDLE_ATTRIBUTES_DOWNLOAD = '_attributes_download';

  const MEDIA_BUNDLE_ATTRIBUTES_TARGET = '_attributes_target';

  const MEDIA_BUNDLE_ATTRIBUTES_REL = '_attributes_rel';

  const MEDIA_BUNDLE_USE_CRON = '_use_cron';

  const MEDIA_BUNDLE_IMAGE_FORMAT = '_format';

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * @var \Drupal\media_pdf_thumbnail\Manager\MediaPdfThumbnailImageManager
   */
  protected MediaPdfThumbnailImageManager $mediaPdfThumbnailImageManager;

  /**
   * @var \Drupal\media_pdf_thumbnail\Manager\PdfImageEntityQueueManager;
   */
  protected PdfImageEntityQueueManager $pdfImageEntityQueueManager;

  /**
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected StreamWrapperManagerInterface $streamWrapperManager;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->configFactory = $container->get('config.factory');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityTypeBundleInfo = $container->get('entity_type.bundle.info');
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->mediaPdfThumbnailImageManager = $container->get('media_pdf_thumbnail.image.manager');
    $instance->pdfImageEntityQueueManager = $container->get('media_pdf_thumbnail.pdf_image_entity.queue.manager');
    $instance->streamWrapperManager = $container->get('stream_wrapper_manager');
    $instance->routeMatch = $container->get('current_route_match');
    $instance->moduleHandler = $container->get('module_handler');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return static::getDefaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    return $this->getSettingsFormElement($element);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return $this->getSettingsSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $element = parent::viewElements($items, $langcode);

    // If empty element.
    if (empty($element)) {
      return $element;
    }

    $entity = $element[0]['#item']->getParent()->getParent()->getEntity();

    // Get settings.
    $settings = $this->getElementSettings($entity);

    if (empty($settings)) {
      return $element;
    }

    // Getting thumbnail info.
    if (!empty($settings[static::IMAGE_USE_CRON])) {
      $fieldInfos = $this->pdfImageEntityQueueManager->getThumbnail($entity,
        $items->getName(),
        $settings[static::PDF_FILE_FIELD_SETTING],
        $settings[static::IMAGE_FORMAT_SETTINGS],
        $settings[static::PDF_PAGE_SETTING]);
    }
    else {
      $fieldInfos = $this->getThumbnail($entity, $items->getName(), $settings[static::PDF_FILE_FIELD_SETTING], $settings[static::IMAGE_FORMAT_SETTINGS], $settings[static::PDF_PAGE_SETTING]);
    }

    if (!$fieldInfos) {
      return $element;
    }

    // Rendering image.
    if (!empty($fieldInfos['image_id'])) {
      $element[0]['#image_style'] = $settings['image_style'];
      $element = $this->renderImage($element, $fieldInfos['image_id'], $entity);
    }

    // Get html attributes.
    $options = $this->htmlAttributes($settings);

    // Linking image.
    switch ($settings['image_link']) {
      case 'pdf_file':
        if (!empty($fieldInfos['pdf_uri'])) {
          $stream = $this->streamWrapperManager->getViaUri($fieldInfos['pdf_uri'])->getExternalUrl();
          $element[0]['#url'] = Url::fromUri($stream, $options);
        }
        break;
      case 'content':
        $element[0]['#url'] = $entity->toUrl('canonical', $options);
        break;
      case 'file':
        if (!empty($fieldInfos['pdf_uri'])) {
          $stream = $this->streamWrapperManager->getViaUri($fieldInfos['image_uri'])->getExternalUrl();
          $element[0]['#url'] = Url::fromUri($stream, $options);
        }
        break;
    }

    // Invokes preprocessing hook
    $infos = [
      'fieldInfo' => $fieldInfos,
      'mediaEntity' => $entity,
      'pdfEntity' => !empty($fieldInfos['image_uri']) ? $this->mediaPdfThumbnailImageManager->getPdfEntityByPdfFileUri($fieldInfos['image_uri']) : NULL,
    ];
    $this->moduleHandler->alter('media_pdf_thumbnail_image_render', $element, $infos);

    return $element;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $imageFieldName
   * @param $fileFieldName
   * @param $imageFormat
   * @param $page
   *
   * @return array|false|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function getThumbnail(EntityInterface $entity, $imageFieldName, $fileFieldName, $imageFormat, $page = 1) {

    $fieldInfos = $this->mediaPdfThumbnailImageManager->createThumbnail($entity, $fileFieldName, $imageFormat, $page);

    if (!$fieldInfos) {
      return FALSE;
    }

    if (empty($fieldInfos['image_id'])) {

      $fileEntity = $this->mediaPdfThumbnailImageManager->getFileEntityFromField($entity, $fileFieldName);
      $imageId = $entity->getEntityTypeId() !== 'media' && $entity->hasField($imageFieldName) ? $entity->get($imageFieldName)->target_id : $this->mediaPdfThumbnailImageManager->getGenericThumbnail();

      $fieldInfos = [
        'image_id' => $imageId,
        'pdf_uri' => $fileEntity->getFileUri(),
      ];
    }

    return $fieldInfos;
  }

  /**
   * @return array
   */
  protected function getParentEntityFields(): array {
    $entityTypeId = $this->fieldDefinition->getTargetEntityTypeId();
    $targetBundle = $this->fieldDefinition->getTargetBundle();
    $targetBundle = !empty($targetBundle) ? $targetBundle : $this->routeMatch->getRawParameter('media_type');
    $output = [];

    if (!empty($targetBundle)) {
      foreach ($this->entityFieldManager->getFieldDefinitions($entityTypeId, $targetBundle) as $fieldDefinition) {
        if ($fieldDefinition->getType() == 'file') {
          $output[$fieldDefinition->getName()] = $fieldDefinition->getLabel();
        }
      }
    }
    // If no specific bundle.
    else {
      $bundleInfos = $this->entityTypeBundleInfo->getBundleInfo($entityTypeId);
      foreach ($bundleInfos as $id => $bundleInfo) {
        foreach ($this->entityFieldManager->getFieldDefinitions($entityTypeId, $id) as $fieldDefinition) {
          if ($fieldDefinition->getType() == 'file') {
            $output[$fieldDefinition->getName()] = $fieldDefinition->getLabel();
          }
        }
      }
    }

    return $output;
  }

}
