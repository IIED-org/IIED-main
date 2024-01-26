<?php

namespace Drupal\content_translation_redirect\EventSubscriber;

use Drupal\content_translation_redirect\ContentTranslationRedirectEvents;
use Drupal\content_translation_redirect\ContentTranslationRedirectManagerInterface;
use Drupal\content_translation_redirect\Event\ContentTranslationRedirectEvent;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Redirect subscriber for controller requests.
 */
class ContentTranslationRedirectRequestSubscriber implements EventSubscriberInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The storage of Content Translation Redirect entities.
   *
   * @var \Drupal\content_translation_redirect\ContentTranslationRedirectStorageInterface
   */
  protected $storage;

  /**
   * The content translation redirect manager.
   *
   * @var \Drupal\content_translation_redirect\ContentTranslationRedirectManagerInterface
   */
  protected $manager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * ContentTranslationRedirectRequestSubscriber constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\content_translation_redirect\ContentTranslationRedirectManagerInterface $manager
   *   The content translation redirect manager.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(RouteMatchInterface $route_match, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager, ContentTranslationRedirectManagerInterface $manager, EventDispatcherInterface $event_dispatcher) {
    $this->routeMatch = $route_match;
    $this->languageManager = $language_manager;
    $this->storage = $entity_type_manager->getStorage('content_translation_redirect');
    $this->manager = $manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Handles the redirect if any found.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function onRequest(RequestEvent $event): void {
    // Check that the site has more than one language.
    if (!$this->languageManager->isMultilingual()) {
      return;
    }

    $entity = $this->getEntity();
    if ($entity === NULL) {
      return;
    }

    // Check that the entity type is supported.
    $entity_type = $entity->getEntityType();
    if (!$this->manager->isEntityTypeSupported($entity_type)) {
      return;
    }

    // Check that the entity language is different from the current language.
    $language = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
    if ($language->getId() === $entity->language()->getId()) {
      return;
    }

    // Check the redirect entity with a status code.
    $redirect = $this->storage->loadByEntity($entity);
    if ($redirect === NULL || $redirect->getStatusCode() === NULL) {
      return;
    }

    // Check whether we should act on translatable entity only or not.
    if ($redirect->translatableEntityOnly() && !$entity->isTranslatable()) {
      return;
    }

    // Check whether we should act on untranslatable entity only or not.
    if ($redirect->untranslatableEntityOnly() && $entity->isTranslatable()) {
      return;
    }

    $url = Url::fromRoute('<current>');
    $current_url = $url->setAbsolute()->toString();

    $url = $redirect->getUrl() ?? $url->setOption('language', $entity->getUntranslated()->language());
    $redirect_url = $url->setAbsolute()->toString();

    // Check the difference between URLs.
    if ($current_url === $redirect_url) {
      return;
    }

    $cacheable_metadata = new CacheableMetadata();
    $cacheable_metadata->setCacheContexts(['route', 'languages:' . LanguageInterface::TYPE_CONTENT]);
    $cacheable_metadata->setCacheTags(Cache::buildTags('content_translation_redirect', $this->storage->getPossibleIds($entity)));

    $response = new TrustedRedirectResponse($redirect_url, $redirect->getStatusCode());
    $response->addCacheableDependency($cacheable_metadata);
    $response->addCacheableDependency($redirect);
    $response->addCacheableDependency($entity);

    $redirect_event = new ContentTranslationRedirectEvent($response, $entity, $language, $redirect, $url);
    $this->eventDispatcher->dispatch($redirect_event, ContentTranslationRedirectEvents::REDIRECT);

    $event->setResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST][] = ['onRequest'];
    return $events;
  }

  /**
   * Gets an entity from the current route match.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   An entity object. NULL if no matching entity is found.
   */
  protected function getEntity(): ?ContentEntityInterface {
    $route_name = $this->routeMatch->getRouteName();
    if ($route_name === NULL) {
      return NULL;
    }

    // Trying to find an entity in route parameters.
    foreach ($this->routeMatch->getParameters() as $parameter) {
      if ($parameter instanceof ContentEntityInterface) {
        try {
          // Compare the entity canonical route with the current route.
          if ($route_name === $parameter->toUrl()->getRouteName()) {
            return $parameter;
          }
        }
        catch (\Exception $e) {
          // There is no canonical route for this entity,
          // proceed to the next parameter.
          continue;
        }
      }
    }
    return NULL;
  }

}
