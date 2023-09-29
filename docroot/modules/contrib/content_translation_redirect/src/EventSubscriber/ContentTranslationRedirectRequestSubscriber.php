<?php

namespace Drupal\content_translation_redirect\EventSubscriber;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

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
   * ContentTranslationRedirectRequestSubscriber constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RouteMatchInterface $route_match, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->routeMatch = $route_match;
    $this->languageManager = $language_manager;
    $this->storage = $entity_type_manager->getStorage('content_translation_redirect');
  }

  /**
   * Handles the redirect if any found.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function onRequestCheckRedirect(RequestEvent $event): void {
    $entity = $this->getEntity();

    // Check the translatable entity.
    if (!($entity instanceof ContentEntityInterface)) {
      return;
    }

    // Check the entity translation to the current language.
    $language = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
    if ($language->getId() === $entity->language()->getId()) {
      return;
    }

    // Check the redirect entity with a status code.
    $redirect = $this->storage->loadByEntity($entity);
    if (!$redirect || !$redirect->getStatusCode()) {
      return;
    }

    // Check whether we should act on translatable entity only or not.
    if ($redirect->translatableEntityOnly() && !$entity->isTranslatable()) {
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

    // Set the redirect response.
    $response = new LocalRedirectResponse($redirect_url, $redirect->getStatusCode());
    $response->addCacheableDependency($url);
    $event->setResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequestCheckRedirect'];
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
    if (!$route_name) {
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
