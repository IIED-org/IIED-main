<?php

namespace Drupal\linkchecker\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\linkchecker\LinkCheckerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Promise\Utils;

/**
 * Checks the link.
 *
 * @QueueWorker(
 *   id = "linkchecker_check",
 *   title = @Translation("LinkChecker check"),
 *   cron = {"time" = 240}
 * )
 */
class LinkCheck extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The link checker service.
   *
   * @var \Drupal\linkchecker\LinkCheckerService
   */
  protected $linkChecker;

  /**
   * LinkExtract constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, LinkCheckerService $linkChecker) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->linkChecker = $linkChecker;
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
      $container->get('linkchecker.checker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $promises = [];

    // Collect all request promises.
    foreach ($data as $id) {
      /** @var \Drupal\linkchecker\LinkCheckerLinkInterface $link */
      $link = $this->entityTypeManager
        ->getStorage('linkcheckerlink')
        ->load($id);

      if ($link && !is_null($link->getUrl())) {
        $promises[] = $this->linkChecker->check($link);
      }
    }

    // Force wait to complete of all requests
    // to prevent next items of queue to be run.
    Utils::settle($promises)->wait();
  }

}
