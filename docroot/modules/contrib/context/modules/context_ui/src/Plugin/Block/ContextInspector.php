<?php

namespace Drupal\context_ui\Plugin\Block;

use Drupal\context\ContextManager;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\devel\DevelDumperManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'context inspector' block.
 *
 * @Block(
 *   id = "context_inspector",
 *   admin_label = @Translation("Context inspector"),
 *   category = @Translation("Debugging")
 * )
 */
class ContextInspector extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected ModuleHandler $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The context manager.
   *
   * @var \Drupal\context\ContextManager
   */
  protected ContextManager $contextManager;

  /**
   * The devel dumper.
   *
   * @var \Drupal\devel\DevelDumperManager
   */
  protected ?DevelDumperManager $develDumper;

  /**
   * Constructs a new ContextInspector block.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\context\ContextManager $context_manager
   *   The context manager.
   * @param \Drupal\devel\DevelDumperManager $devel_dumper
   *   The devel dumper manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ModuleHandler $module_handler,
    AccountProxyInterface $current_user,
    ContextManager $context_manager,
    DevelDumperManager|null $devel_dumper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->contextManager = $context_manager;
    $this->develDumper = $devel_dumper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ContextInspector {
    $devel_dumper = ($container->has('devel.dumper')) ? $container->get('devel.dumper') : NULL;
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('context.manager'),
      $devel_dumper
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $module = $this->moduleHandler->moduleExists('devel');
    $permission = $this->currentUser->hasPermission('access devel information');
    if ($module && $permission) {
      /** @codingStandardsIgnoreStart * */
      $output = $this->develDumper->dumpOrExport($this->contextManager->getActiveContexts(), NULL, TRUE);
      /** @codingStandardsIgnoreEnd * */
    }
    elseif ($module && !$permission) {
      $output = $this->t('You do not have permissions to view debug content.');
    }
    elseif (!$module) {
      $output = $this->t('Please enable the devel module to use the context inspector.');
    }
    $build = [
      '#type' => 'markup',
      '#markup' => $output,
    ];
    return isset($output) ? $build : [];
  }

}
