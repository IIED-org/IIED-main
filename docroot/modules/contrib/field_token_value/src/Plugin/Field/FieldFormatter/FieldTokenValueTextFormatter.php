<?php

namespace Drupal\field_token_value\Plugin\Field\FieldFormatter;

use Drupal\Core\Link;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;

/**
 * Provides the Field Token Value Text field formatter.
 *
 * @FieldFormatter(
 *   id = "field_token_value_text",
 *   label = @Translation("Text"),
 *   field_types = {
 *     "field_token_value"
 *   }
 * )
 */
class FieldTokenValueTextFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The field token value wrapper manager.
   *
   * @var \Drupal\field_token_value\WrapperManager
   */
  protected $wrappers;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The Drupal module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $wrapper_class = new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings']
    );

    $wrapper_class->wrappers = $container->get('field_token_value.wrapper_manager');
    $wrapper_class->renderer = $container->get('renderer');
    $wrapper_class->moduleHandler = $container->get('module_handler');

    return $wrapper_class;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'wrapper' => '',
      'link' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['wrapper'] = [
      '#type' => 'select',
      '#title' => $this->t('Wrapper'),
      '#description' => $this->t('The wrapper to use for the field output.'),
      '#default_value' => $this->getSetting('wrapper'),
      '#options' => $this->wrappers->getWrapperOptions(),
      '#empty_option' => $this->t('- Select wrapper -'),
    ];

    $element['link'] = [
      '#title' => $this->t('Link field value to entity'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('link'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $selected = $this->getSetting('wrapper');

    if (!empty($selected)) {
      $wrapper = $this->wrappers->getDefinition($selected);
      if ($this->getSetting('link')) {
        $summary[] = $this->t('Display: @summary and linked', ['@summary' => $wrapper['summary']]);
      }
      else {
        $summary[] = $this->t('Display: @summary', ['@summary' => $wrapper['summary']]);
      }
    }
    else {
      $summary[] = $this->t('No wrapper has been selected so a paragraph tag will be used by default and linked');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $selected = $this->getSetting('wrapper');
    $output_as_link = $this->getSetting('link');
    $entity = $items->getEntity();

    // Because the field value is determined by the instance settings, even if
    // the user somehow managed to add multiple items, the same value will be
    // set for each one. Because of this we only ever use the first value.
    if (!empty($items[0])) {
      $element[0] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $items[0]->value,
      ];

      // Update the output value based on the link setting.
      if ($output_as_link && !$entity->isNew()) {
        try {
          $uri = $entity->toUrl();
        }
        catch (UndefinedLinkTemplateException $e) {
          // This exception is thrown by \Drupal\Core\Entity\Entity::urlInfo()
          // and it means that the entity type doesn't have a link template nor
          // a valid "uri_callback", so don't bother trying to output a link for
          // the rest of the referenced entities.
          $output_as_link = FALSE;
        }
      }

      if ($output_as_link && isset($uri) && !$entity->isNew()) {
        $link = Link::fromTextAndUrl($element[0]['#value'], $uri)->toRenderable();
        $element[0]['#value'] = $this->renderer->render($link);
      }

      if (!empty($selected)) {
        // Retrieve the wrapper info from the service.
        $wrapper_info = $this->wrappers->getDefinition($selected);

        // Cater for the wrapper having no tag.
        if (empty($wrapper_info['tag'])) {
          $element[0] = [
            '#markup' => $items[0]->value,
          ];
        }
        else {
          // Update the output tag based on the wrapper info.
          $element[0]['#tag'] = $wrapper_info['tag'];

          // If the wrapper contains attributes such as class, add them in.
          if (isset($wrapper_info['attributes'])) {
            $element[0]['#attributes'] = $wrapper_info['attributes'];
          }
        }

        // Allow modules to alter the output of the field. For example to
        // possibly attach CSS or JS for a particular tag.
        $this->moduleHandler->alter('field_token_value_output', $element[0], $wrapper_info);
      }
    }

    return $element;
  }

}
