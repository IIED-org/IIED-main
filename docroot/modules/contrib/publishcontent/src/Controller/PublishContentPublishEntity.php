<?php

namespace Drupal\publishcontent\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\core\Access\AccessResult;
use Drupal\Core\Url;

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
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->server = $container->get('request_stack')
      ->getCurrentRequest()->server;
    $instance->languageManager = $container->get('language_manager');
    $instance->messenger = $container->get('messenger');
    $instance->configFactory = $container->get('config.factory');
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
  public function toggleEntityStatus(NodeInterface $node, string $langcode = '') {
    if ($referrer = $this->server->get('HTTP_REFERER')) {
      $redirectUrl = Url::fromUri($referrer, ['absolute' => TRUE])->getUri();
    }
    else {
      $redirectUrl = $node->toUrl()->toString();
    }

    // If node is not translatable, just publish or unpublish it.
    if (!$node->isTranslatable()) {
      $node->setPublished(!$node->isPublished());
      $node->save();
      return new RedirectResponse($redirectUrl);
    }

    if ($langcode == '') {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
    }

    if (!$node->hasTranslation($langcode)) {
      $this->messenger->addError($this->t("You can't publish/unpublish a non-existing translation."));
      return new RedirectResponse($redirectUrl);
    }

    /** @var \Drupal\node\NodeInterface $translatedNode */
    if ($translatedNode = $node->getTranslation($langcode)) {
      $node = $translatedNode;
    }

    $node->setPublished(!$node->isPublished());
    $node->save();

    return new RedirectResponse($redirectUrl);
  }

  /**
   * A custom route access callback for the Publish/Unpublish local task UI.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function hasUILocalTask() {
    $config = $this->configFactory->get('publishcontent.settings');
    return AccessResult::allowedIf(!empty($config) &&
      !empty($config->get('ui_localtask')));
  }

}
