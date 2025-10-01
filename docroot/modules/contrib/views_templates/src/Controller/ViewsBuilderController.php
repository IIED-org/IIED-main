<?php

namespace Drupal\views_templates\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ViewBuilderController class.
 */
class ViewsBuilderController extends ControllerBase {

  /**
   * The plugin builder service.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $builderManager;

  /**
   * Constructs a new ViewsBuilderController object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $builderManager
   *   The Views Builder Plugin Interface.
   */
  public function __construct(PluginManagerInterface $builderManager) {
    $this->builderManager = $builderManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.views_templates.builder')
    );
  }

  /**
   * Create template list table.
   *
   * @return array
   *   Render array of template list.
   */
  public function templateList() {
    $table = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Description'),
        $this->t('Add'),
      ],
      '#empty' => $this->t('There are no available Views Templates'),
    ];

    /** @var \Drupal\views_templates\Plugin\ViewsBuilderPluginInterface $definition */
    foreach ($this->builderManager->getDefinitions() as $definition) {

      /** @var \Drupal\views_templates\Plugin\ViewsBuilderPluginInterface $builder */
      $builder = $this->builderManager->createInstance($definition['id']);
      if ($builder->templateExists()) {
        $plugin_id = $builder->getPluginId();
        $row = [
          'name' => ['#plain_text' => $builder->getAdminLabel()],
          'description' => ['#plain_text' => $builder->getDescription()],
          'add' => [
            '#type' => 'link',
            '#title' => $this->t('Add'),
            '#url' => Url::fromRoute('views_templates.create_from_template',
              [
                'view_template' => $plugin_id,
              ]
            ),
          ],
        ];
        $table[$plugin_id] = $row;
      }
    }

    return $table;
  }

}
