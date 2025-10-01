<?php

namespace Drupal\views_templates\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Error;
use Drupal\views\Entity\View;
use Drupal\views_templates\ViewsTemplateLoaderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

/**
 * Confirms existence of template and handles duplication.
 */
abstract class ViewsDuplicateBuilderBase extends ViewsBuilderBase implements ViewsDuplicateBuilderPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The view template loader.
   *
   * @var \Drupal\views_templates\ViewsTemplateLoaderInterface
   */
  protected $templateLoader;

  /**
   * The loaded template.
   *
   * @var mixed
   */
  protected $loadedTemplate;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructor to the class ViewDuplicateBuilderBase.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ViewsTemplateLoaderInterface $loader, ?LoggerInterface $logger = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->templateLoader = $loader;

    if ($logger === NULL) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $logger argument is deprecated in drupal:10.1.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/2932520', E_USER_DEPRECATED);
      // @phpstan-ignore-next-line
      $logger = \Drupal::service('logger.channel.views_templates');
    }
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('views_templates.loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function createView($options = NULL) {
    if ($view_template = $this->loadTemplate($options)) {
      $view_template['id'] = $options['id'];
      $view_template['label'] = $options['label'];
      $view_template['description'] = $options['description'];
      return View::create($view_template);
    }
    return NULL;

  }

  /**
   * {@inheritdoc}
   */
  public function getViewTemplateId() {
    return $this->getDefinitionValue('view_template_id');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminLabel() {
    return $this->loadViewsTemplateValue('label');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->loadViewsTemplateValue('description');
  }

  /**
   * Return value from template.
   *
   * @param mixed $key
   *   The Key.
   * @param mixed $options
   *   The options.
   *
   * @return mixed|null
   *   Return the value or a null.
   */
  protected function loadViewsTemplateValue($key, $options = NULL) {
    $view_template = $this->loadTemplate($options);
    if (isset($view_template[$key])) {
      return $view_template[$key];
    }
    return NULL;
  }

  /**
   * Load template from service.
   *
   * @param mixed $options
   *   The options to load template.
   *
   * @return object
   *   Returns loaded template in object form.
   */
  protected function loadTemplate($options) {
    if (empty($this->loadedTemplate)) {
      try {
        $template = $this->templateLoader->load($this);
      }
      catch (FileNotFoundException $e) {
        Error::logException($this->logger, $e);
        return NULL;
      }

      $this->alterViewTemplateAfterCreation($template, $options);
      $this->loadedTemplate = $template;
    }

    return $this->loadedTemplate;
  }

  /**
   * After View Template has been created the Builder should alter it some how.
   *
   * @param array $view_template
   *   The array of view template.
   * @param array $options
   *   Options for altering.
   */
  protected function alterViewTemplateAfterCreation(array &$view_template, array $options = NULL) {
    if ($replacements = $this->getReplacements($options)) {
      $this->replaceTemplateKeyAndValues($view_template, $replacements, $options);
    }
  }

  /**
   * Get the replaces array from the plugin definition.
   *
   * The keys will be converted to work with yml files.
   *
   * @param mixed $options
   *   Options to get replacements.
   *
   * @return array
   *   Returns an array.
   */
  protected function getReplacements($options) {
    if ($replacements = $this->getDefinitionValue('replacements')) {
      $converted_replacements = [];
      foreach ($replacements as $key => $value) {
        $new_key = '__' . strtoupper($key);
        $converted_replacements[$new_key] = $value;
      }
      return $converted_replacements;
    }
    return [];
  }

  /**
   * Recursively replace keys and values in template elements.
   *
   * For example of builder and yml template:
   *
   * @param array $template_elements
   *   Array of elements from a View Template array.
   * @param array $replace_values
   *   The values in that should be replaced in the template.
   *   The keys in this array can be keys OR values template array.
   *   This allows replacing both keys and values in the template.
   * @param mixed $options
   *   The options to replace template key and values.
   *
   * @see Drupal\views_templates_builder_test\Plugin\ViewsTemplateBuilder
   */
  protected function replaceTemplateKeyAndValues(array &$template_elements, array $replace_values, $options = NULL) {
    foreach ($template_elements as $key => &$value) {
      if (is_array($value)) {
        $this->replaceTemplateKeyAndValues($value, $replace_values, $options);
      }
      foreach ($replace_values as $replace_key => $replace_value) {
        if (!is_array($value) && $replace_value !== NULL) {
          if (is_string($value)) {
            if (stripos($value, $replace_key) !== FALSE) {
              $value = str_replace($replace_key, $replace_value, $value);
            }
          }
          elseif ($replace_key === $value) {
            $value = $replace_value;
          }
        }
        if (stripos($key, $replace_key) !== FALSE) {
          // NULL is used in replace value to remove keys from template.
          if ($replace_value !== NULL) {
            $new_key = str_replace($replace_key, $replace_value, $key);
            $template_elements[$new_key] = $value;
          }
          unset($template_elements[$key]);
        }
      }
    }
  }

  /**
   * Check if template exists.
   *
   * @return bool
   *   Returns a boolean value.
   */
  public function templateExists() {
    return $this->loadTemplate([]) ? TRUE : FALSE;
  }

}
