<?php

namespace Drupal\linkchecker\Plugin\QueueWorker;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\linkchecker\Plugin\LinkStatusHandlerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handler a status of a link.
 *
 * @QueueWorker(
 *   id = "linkchecker_status_handle",
 *   title = @Translation("LinkChecker status handle"),
 *   cron = {"time" = 60}
 * )
 */
class LinkStatusHandle extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The status handler manager.
   *
   * @var \Drupal\linkchecker\Plugin\LinkStatusHandlerManager
   */
  protected $statusHandlerManager;

  /**
   * LinkExtract constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, LinkStatusHandlerManager $statusHandlerManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->statusHandlerManager = $statusHandlerManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.link_status_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $response = $data['response'];
    try {
      /** @var \Drupal\linkchecker\Plugin\LinkStatusHandlerInterface $handler */
      $handler = $this->statusHandlerManager->createInstance($data['handler']);
    }
    catch (PluginNotFoundException $e) {
      // Skip it.
    }

    foreach ($data['links'] as $linkId) {
      try {
        /** @var \Drupal\linkchecker\LinkCheckerLinkInterface $link */
        $link = $this->entityTypeManager
          ->getStorage('linkcheckerlink')
          ->load($linkId);

        $handler->handle($link, $response);
      }
      catch (\Exception $e) {
        // If we can`t load Link or entity - just skip it.
      }
    }
  }

}
