<?php

namespace Drupal\publishcontent\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\publishcontent\Event\PublishContentPublishEvent;
use Drupal\publishcontent\Event\PublishContentUnpublishEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Toggles node status.
 */
class PublishContentPublishEntity implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The ServerBag object from the current request.
   *
   * @var \Symfony\Component\HttpFoundation\ServerBag
   */
  protected $server;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The module configuration for reading.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The logging channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * An event dispatcher instance to use for publishing events.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->server = $container->get('request_stack')
      ->getCurrentRequest()->server;
    $instance->languageManager = $container->get('language_manager');
    $instance->messenger = $container->get('messenger');
    $instance->config = $container->get('config.factory')->get('publishcontent.settings');
    $instance->currentUser = $container->get('current_user');
    $instance->logger = $container->get('logger.factory')->get('publishcontent');
    $instance->time = $container->get('datetime.time');
    $instance->eventDispatcher = $container->get('event_dispatcher');
    return $instance;
  }

  /**
   * Toggle node status.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node being toggled.
   * @param string $langcode
   *   The language code of the node.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to the previous page.
   */
  public function toggleEntityStatus(NodeInterface $node, $langcode = '') {
    try {
      if ($referrer = $this->server->get('HTTP_REFERER')) {
        $redirect_url = Url::fromUri($referrer, ['absolute' => TRUE])->getUri();
      }
      else {
        $redirect_url = $node->toUrl()->toString();
      }
    }
    catch (\Exception $e) {
      $redirect_url = Url::fromRoute('<front>')->setAbsolute()->toString();
    }

    if ($node->isTranslatable()) {
      if ($langcode === '') {
        $langcode = $node->language()->getId();
      }

      if (!$node->hasTranslation($langcode)) {
        $this->messenger->addError($this->t("You can't @publish/@unpublish a non-existing translation.", [
          '@publish' => $this->config->get('publish_text_value'),
          '@unpublish' => $this->config->get('unpublish_text_value'),
        ]));
        return new RedirectResponse($redirect_url);
      }

      $node = $node->getTranslation($langcode);
    }

    if ($node->isPublished()) {
      $node->setUnpublished();

      $event = new PublishContentUnpublishEvent($node, $langcode);
      $this->eventDispatcher->dispatch($event, PublishContentUnpublishEvent::class);
    }
    else {
      $node->setPublished();

      $event = new PublishContentPublishEvent($node, $langcode);
      $this->eventDispatcher->dispatch($event, PublishContentPublishEvent::class);
    }

    $is_published = $node->isPublished();
    $status = $is_published ? $this->config->get('publish_text_value') : $this->config->get('unpublish_text_value');

    if (!empty($this->config)) {
      if ($this->config->get('create_log_entry')) {
        $this->logger->notice($this->t('@type: @action @title', [
          '@type' => $node->bundle(),
          '@action' => $is_published ? $this->t('published') : $this->t('unpublished'),
          '@title' => $node->getTitle(),
        ]));
      }

      if ($this->config->get('create_revision')) {
        $node->setNewRevision(TRUE);
        $node->revision_log = $this->t('Changed to @status by @user', [
          '@status' => $status,
          '@user' => $this->currentUser->getDisplayName(),
        ]);
        $node->setRevisionCreationTime($this->time->getRequestTime());
        $node->setRevisionUserId($this->currentUser->id());
      }

    }

    try {
      $node->save();
      // Allow flexible translation for the message.
      if ($is_published) {
        $message = $this->t('%title has been published.', [
          '%title' => $node->getTitle(),
        ]);
      }
      else {
        $message = $this->t('%title has been unpublished.', [
          '%title' => $node->getTitle(),
        ]);
      }
      $this->messenger->addMessage($message);
    }
    catch (EntityStorageException $e) {
    }

    return new RedirectResponse($redirect_url);
  }

  /**
   * A custom route access callback for the Publish/Unpublish local task UI.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function hasUiLocalTask() {
    return AccessResult::allowedIf(!empty($this->config) &&
      !empty($this->config->get('ui_localtask')));
  }

}
