<?php

namespace Drupal\media_pdf_thumbnail\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Url;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\media_pdf_thumbnail\Manager\MediaPdfThumbnailImageManager;
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

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\media_pdf_thumbnail\Manager\MediaPdfThumbnailImageManager
   */
  protected $mediaPdfThumbnailImageManager;

  /**
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * MediaPdfThumbnailImageFieldFormatter constructor.
   *
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param $label
   * @param $view_mode
   * @param array $third_party_settings
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   * @param \Drupal\media_pdf_thumbnail\Manager\MediaPdfThumbnailImageManager $mediaPdfThumbnailImageManager
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage, ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo, EntityFieldManagerInterface $entityFieldManager, MediaPdfThumbnailImageManager $mediaPdfThumbnailImageManager, StreamWrapperManagerInterface $streamWrapperManager, RouteMatchInterface $routeMatch) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage);
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->entityFieldManager = $entityFieldManager;
    $this->mediaPdfThumbnailImageManager = $mediaPdfThumbnailImageManager;
    $this->streamWrapperManager = $streamWrapperManager;
    $this->routeMatch = $routeMatch;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return \Drupal\Core\Field\FormatterBase|\Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter|\Drupal\media_pdf_thumbnail\Plugin\Field\FieldFormatter\MediaPdfThumbnailImageFieldFormatter|static
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager'),
      $container->get('media_pdf_thumbnail.image.manager'),
      $container->get('stream_wrapper_manager'),
      $container->get('current_route_match'));
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {

    $settings = [
      'pdf_file_field' => '',
      'pdf_page' => '',
      'image_link_attributes_download' => '',
      'image_link_attributes_target' => '',
      'image_link_attributes_rel' => '',
    ];

    foreach (_media_pdf_thumbnail_getFieldsList() as $bundleId => $infos) {
      $settings[$bundleId . '_page'] = '';
      $settings[$bundleId . '_field'] = '';
      $settings[$bundleId . '_enable'] = '';
      $settings[$bundleId . '_link'] = '';
      $settings[$bundleId . '_attributes_download'] = '';
      $settings[$bundleId . '_attributes_target'] = '';
      $settings[$bundleId . '_attributes_rel'] = '';
    }
    return $settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $parentFileFields = $this->getParentEntityFields();
    if (!empty($parentFileFields)) {

      $element['pdf_file_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Field containing the pdf'),
        '#options' => $parentFileFields,
        '#default_value' => $this->getSetting('pdf_file_field'),
      ];

      $element['pdf_page'] = [
        '#type' => 'number',
        '#title' => $this->t('Choose the page of the pdf to get the image from'),
        '#min' => 1,
        '#default_value' => !empty($this->getSetting('pdf_page')) ? $this->getSetting('pdf_page') : 1,
      ];

      $element['image_link']['#options']['pdf_file'] = $this->t('PDF File');
      $element['image_link']['#weight'] = 0;
      $element['image_link']['#attributes']['class'][] = 'thumbnail-pdf-link';

      $element['image_link_attributes_download'] = [
        '#prefix' => '<div id="thumbnail-pdf-link-attributes" class="fieldset"><div class="fieldset__legend"><span class="fieldset__label">' . $this->t('HTML attributes') . '</span><div class="fieldset__wrapper">',
        '#type' => 'checkbox',
        '#title' => 'download',
        '#weight' => 0,
        '#default_value' => $this->getSetting('image_link_attributes_download'),
      ];

      $element['image_link_attributes_target'] = [
        '#type' => 'textfield',
        '#title' => $this->t('target') . ' :',
        '#description' => 'Ex: _self, _blank, _parent, _top',
        '#weight' => 0,
        '#size' => 5,
        '#default_value' => $this->getSetting('image_link_attributes_target'),
      ];

      $element['image_link_attributes_rel'] = [
        '#suffix' => '</div></div></div>',
        '#type' => 'textfield',
        '#title' => $this->t('rel') . ' :',
        '#description' => 'Ex: alternate, author, bookmark, icon, nofollow, etc..',
        '#size' => 5,
        '#weight' => 0,
        '#default_value' => $this->getSetting('image_link_attributes_rel'),
      ];

      $element['#attached']['library'] = 'media_pdf_thumbnail/field_formatter_form';

    }
    return $element;
  }

  /**
   * @return array
   */
  protected function getParentEntityFields() {
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

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = [];

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('image_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = t('Image style: @style', ['@style' => $image_styles[$image_style_setting]]);
    }
    else {
      $summary[] = t('Original image');
    }

    $link_types = [
      'content' => t('Linked to content'),
      'file' => t('Linked to file'),
      'pdf_file' => t('Linked to pdf file'),
    ];

    // Display this setting only if image is linked.
    $image_link_setting = $this->getSetting('image_link');
    if (isset($link_types[$image_link_setting])) {
      $summary[] = $link_types[$image_link_setting];
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    // All settings.
    $settings = $this->getSettings();

    // Settings from entity view mode field formatter.
    $imageLinkSetting = $this->getSetting('image_link');
    $imageLinkDownload = $this->getSetting('image_link_attributes_download');
    $imageLinkTarget = $this->getSetting('image_link_attributes_target');
    $imageLinkRel = $this->getSetting('image_link_attributes_rel');
    $pdfFileField = $this->getSetting('pdf_file_field');
    $pdfPage = $this->getSetting('pdf_page');

    $element = parent::viewElements($items, $langcode);

    // If empty element.
    if (empty($element)) {
      return $element;
    }

    $entity = $element[0]['#item']->getParent()->getParent()->getEntity();

    // If no settings from entity view mode field formatter, it means it's a single field render.
    if (empty($pdfFileField)) {
      $bundle = $entity->bundle();
      // Search for available options in all settings.
      if (!empty($settings[$bundle . '_enable']) && !empty($settings[$bundle . '_field'])) {
        $pdfFileField = $settings[$bundle . '_field'];
        $pdfPage = $settings[$bundle . '_page'];
        $imageLinkSetting = $settings[$bundle . '_link'];
        $imageLinkDownload = $settings[$bundle . '_attributes_download'];
        $imageLinkTarget = $settings[$bundle . '_attributes_target'];
        $imageLinkRel = $settings[$bundle . '_attributes_rel'];
      }
      else {
        $this->mediaPdfThumbnailImageManager->getGenericThumbnail();
        return $element;
      }
    }

    // Getting thumbnail info.
    $fieldInfos = $this->getThumbnail($entity, $pdfFileField, $pdfPage);

    // Rendering image.
    if (!empty($fieldInfos['image_id'])) {
      /**
       * @var \Drupal\image\Plugin\Field\FieldType\ImageItem $imageItem
       */
      $imageItem = $element[0]['#item'];
      $value = $imageItem->getValue();
      $value['target_id'] = $fieldInfos['image_id'];
      $imageItem->setValue($value);
      $element[0]['#item'] = $imageItem;
    }

    // Linking image to pdf to file.
    if ($imageLinkSetting == 'pdf_file' && !empty($fieldInfos['pdf_uri'])) {
      $stream = $this->streamWrapperManager->getViaUri($fieldInfos['pdf_uri'])->getExternalUrl();

      // Set html attributes.
      $options = [];
      if (!empty($imageLinkDownload)) {
        $options['attributes']['download'] = '';
      }
      if (!empty($imageLinkTarget)) {
        $options['attributes']['target'] = $imageLinkTarget;
      }
      if (!empty($imageLinkRel)) {
        $options['attributes']['rel'] = $imageLinkRel;
      }

      $element[0]['#url'] = Url::fromUri($stream, $options);
    }
    return $element;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $field
   * @param $page
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]|false|void|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function getThumbnail(EntityInterface $entity, $field, $page) {
    return $this->mediaPdfThumbnailImageManager->createThumbnail($entity, $field, $page);
  }

}
