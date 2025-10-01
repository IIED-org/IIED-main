<?php

namespace Drupal\views_templates;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\views_templates\Plugin\ViewsDuplicateBuilderPluginInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

/**
 * Service class to load templates from the file system.
 */
class ViewsTemplateLoader implements ViewsTemplateLoaderInterface {

  /**
   * The list of available modules.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionListModule;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The list of available modules.
   */
  public function __construct(ModuleExtensionList $extension_list_module) {
    $this->extensionListModule = $extension_list_module;
  }

  /**
   * {@inheritdoc}
   */
  public function load(ViewsDuplicateBuilderPluginInterface $builder) {
    $templates = &drupal_static(__FUNCTION__, []);

    $template_id = $builder->getViewTemplateId();
    if (!isset($templates[$template_id])) {
      $dir = $this->extensionListModule->getPath($builder->getDefinitionValue('provider')) . '/views_templates';
      if (is_dir($dir)) {

        $file_path = $dir . '/' . $builder->getViewTemplateId() . '.yml';
        if (is_file($file_path)) {
          $templates[$template_id] = Yaml::decode(file_get_contents($file_path));
        }
        else {
          throw new FileNotFoundException($file_path);
        }
      }
    }
    return $templates[$template_id];
  }

}
