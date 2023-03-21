<?php

namespace Drupal\linkchecker\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Session\UserSession;
use Drupal\linkchecker\LinkCheckerLinkInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Link status handler plugins.
 */
abstract class LinkStatusHandlerBase extends PluginBase implements LinkStatusHandlerInterface, ContainerFactoryPluginInterface {

  /**
   * The queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The account switcher.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * The Linkchecker settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $linkcheckerSetting;

  /**
   * Number of items per batch.
   *
   * @var int
   */
  protected $itemsPerBatch;

  /**
   * LinkStatusHandlerBase constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueueFactory $queueFactory, EntityTypeManagerInterface $entityTypeManager, AccountSwitcherInterface $accountSwitcher, ImmutableConfig $linkcheckerSetting) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->queue = $queueFactory->get('linkchecker_status_handle');
    $this->entityTypeManager = $entityTypeManager;
    $this->accountSwitcher = $accountSwitcher;
    $this->linkcheckerSetting = $linkcheckerSetting;
    $this->itemsPerBatch = 10;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('queue'),
      $container->get('entity_type.manager'),
      $container->get('account_switcher'),
      $container->get('config.factory')->get('linkchecker.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function queueItems(LinkCheckerLinkInterface $link, ResponseInterface $response) {
    $items = $this->getItems($link, $response);

    if (empty($this->queue->numberOfItems())) {
      $this->queue->createQueue();
    }

    foreach ($items as $item) {
      $data = [];
      $data['links'] = $item;
      $data['response'] = $response;
      $data['handler'] = $this->getPluginId();
      $this->queue->createItem($data);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function handle(LinkCheckerLinkInterface $link, ResponseInterface $response) {
    $this->switchSession();

    $entity = $link->getParentEntity();
    // Fields could be translatable.
    // We should work with translation only.
    if ($entity instanceof TranslatableInterface) {
      if ($entity->hasTranslation($link->getParentEntityLangcode())) {
        $entity = $entity->getTranslation($link->getParentEntityLangcode());
        $this->doHandle($link, $response, $entity);
      }
    }
    else {
      $this->doHandle($link, $response, $entity);
    }

    $this->switchSessionBack();
  }

  /**
   * Handles a status code of link.
   *
   * @param \Drupal\linkchecker\LinkCheckerLinkInterface $link
   *   The link.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response of link checking.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity with proper translation loaded.
   */
  abstract protected function doHandle(LinkCheckerLinkInterface $link, ResponseInterface $response, FieldableEntityInterface $entity);

  /**
   * Gets list of items to queue.
   *
   * List should be like:
   * [
   *   [
   *     'entity_type' => 'node',
   *     'entity_id' => 123,
   *     'link_id' => 12
   *   ],
   *   ...
   *   [
   *     'entity_type' => 'paragraph',
   *     'entity_id' => 1234,
   *     'link_id' => 123
   *   ],
   * ]
   *
   * @param \Drupal\linkchecker\LinkCheckerLinkInterface $link
   *   The link.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response of link checking.
   *
   * @return array
   *   Array of items.
   */
  protected function getItems(LinkCheckerLinkInterface $link, ResponseInterface $response) {
    $linkStorage = $this->entityTypeManager->getStorage($link->getEntityTypeId());
    $query = $linkStorage->getQuery();
    $query->accessCheck();
    $query->condition('urlhash', $link->getHash());
    $linkIds = $query->execute();

    return array_chunk($linkIds, $this->itemsPerBatch, TRUE);
  }

  /**
   * Helper function to switch session.
   */
  protected function switchSession() {
    // Switch anonymous user to an admin.
    $this->accountSwitcher->switchTo(new UserSession(['uid' => user_load_by_name($this->linkcheckerSetting->get('error.impersonate_account'))]));
  }

  /**
   * Helper function to switch session back.
   */
  protected function switchSessionBack() {
    $this->accountSwitcher->switchBack();
  }

}
