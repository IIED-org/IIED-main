<?php

namespace Drupal\pdfpreview\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\pdfpreview\PDFPreviewGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'pdfpreview' formatter.
 *
 * @FieldFormatter(
 *   id = "pdfpreview",
 *   label = @Translation("PDF Preview"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class PDFPreviewFormatter extends ImageFormatter {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * The PDF Preview generator.
   * @var \Drupal\pdfpreview\PDFPreviewGenerator
   */
  protected $pdfPreviewGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setConfigFactory($container->get('config.factory'));
    $instance->setImageFactory($container->get('image.factory'));
    $instance->setPdfPreviewGenerator($container->get('pdfpreview.generator'));
    return $instance;
  }

  /**
   * Sets the config factory.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Sets the image factory.
   *
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   */
  public function setImageFactory(ImageFactory $image_factory) {
    $this->imageFactory = $image_factory;
  }

  /**
   * Sets the PDF Preview generator.
   *
   * @param \Drupal\pdfpreview\PDFPreviewGenerator $pdf_preview_generator
   *   The PDF Preview generator.
   */
  public function setPdfPreviewGenerator(PDFPreviewGenerator $pdf_preview_generator) {
    $this->pdfPreviewGenerator = $pdf_preview_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $config = \Drupal::config('pdfpreview.settings');
    return [
      'show_description' => $config->get('show_description'),
      'tag' => $config->get('tag'),
      'fallback_formatter' => $config->get('fallback_formatter'),
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['show_description'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Show file description beside image'),
      '#options' => [0 => $this->t('No'), 1 => $this->t('Yes')],
      '#default_value' => $this->getSetting('show_description'),
    ];
    $form['tag'] = [
      '#type' => 'radios',
      '#title' => $this->t('HTML tag'),
      '#description' => $this->t('Select which kind of HTML element will be used to theme elements'),
      '#options' => ['span' => 'span', 'div' => 'div'],
      '#default_value' => $this->getSetting('tag'),
    ];
    $form['fallback_formatter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fallback to default file formatter'),
      '#description' => $this->t('When enabled, non-PDF files will be formatted using a default file formatter.'),
      '#default_value' => (boolean) $this->getSetting('fallback_formatter'),
      '#return_value' => $this->configFactory->get('pdfpreview.settings')->get('fallback_formatter'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Separator tag: @tag', [
      '@tag' => $this->getSetting('tag'),
    ]);
    $summary[] = $this->t('Descriptions: @visibility', [
      '@visibility' => $this->getSetting('show_description') ? $this->t('Visible') : $this->t('Hidden'),
    ]);
    if ($this->getSetting('fallback_formatter')) {
      $summary[] = $this->t('Using the default file formatter for non-PDF files');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $url = NULL;
    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->toUrl();
      }
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $cache_tags = $image_style->getCacheTags();
    }

    /** @var \Drupal\file\Entity\File $file */
    foreach ($files as $delta => $file) {
      $cache_contexts = [];
      if (isset($link_file)) {
        $image_uri = $file->getFileUri();
        // @todo Wrap in file_url_transform_relative(). This is currently
        // impossible. As a work-around, we currently add the 'url.site' cache
        // context to ensure different file URLs are generated for different
        // sites in a multisite setup, including HTTP and HTTPS versions of the
        // same site. Fix in https://www.drupal.org/node/2646744.
        $url = Url::fromUri(file_create_url($image_uri));
        $cache_contexts[] = 'url.site';
      }
      $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      if (isset($item->description)) {
        $item_attributes['alt'] = $item->description;
        $item_attributes['title'] = $item->description;
      }
      $item_attributes['class'][] = 'pdfpreview-file';

      // Separate the PDF previews from the other files.
      $show_preview = FALSE;
      if ($file->getMimeType() == 'application/pdf') {
        $preview_uri = $this->pdfPreviewGenerator->getPDFPreview($file);
        $preview = $this->imageFactory->get($preview_uri);
        if ($preview->isValid()) {
          $show_preview = TRUE;
          $item->uri = $preview_uri;
          $item->width = $preview->getWidth();
          $item->height = $preview->getHeight();
          $elements[$delta] = [
            '#theme' => 'image_formatter',
            '#item' => $item,
            '#item_attributes' => $item_attributes,
            '#image_style' => $image_style_setting,
            '#url' => $url,
            '#cache' => [
              'tags' => $cache_tags,
              'contexts' => $cache_contexts,
            ],
          ];
        }
      }
      if (!$show_preview) {
        $elements[$delta] = [
          '#theme' => 'file_link',
          '#file' => $file,
          '#cache' => [
            'tags' => $file->getCacheTags(),
          ],
        ];
      }

      $elements[$delta]['#description'] = $item->description;
      $elements[$delta]['#theme_wrappers'][] = 'pdfpreview_formatter';
      $elements[$delta]['#settings'] = $this->getSettings();
      $elements[$delta]['#fid'] = $file->id();
    }

    return $elements;
  }

}
