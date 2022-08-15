<?php

namespace Drupal\content_translation_redirect\EventSubscriber;

use Drupal\content_translation_redirect\Form\ContentTranslationRedirectForm;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Url;

/**
 * Redirect subscriber for controller requests.
 */
class RequestSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * Configuration object with default settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Content translation redirects storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $storage;

  /**
   * The currently active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * ContentTranslationRedirectRequestSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The currently active route match object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match) {
    $this->config = $config_factory->get('content_translation_redirect.default');
    $this->languageManager = $language_manager;
    $this->storage = $entity_type_manager->getStorage('content_translation_redirect');
    $this->routeMatch = $route_match;
  }

  /**
   * Handles the redirect if any found.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onRequestCheckRedirect(GetResponseEvent $event) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->checkContentEntityCanonicalRoute();
    if ($entity && $entity->isTranslatable()) {
      // Get current language and entity translation language.
      $current_language = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
      $entity_language = $entity->language();
      // Get redirect entity ID.
      $redirect_entity_id = $entity->getEntityTypeId() . '__' . $entity->bundle();

      // Check translation into current language.
      /** @var \Drupal\content_translation_redirect\ContentTranslationRedirectInterface $redirect_entity */
      if ($entity_language->getId() != $current_language->getId()) {
        if (!$redirect_entity = $this->storage->load($redirect_entity_id)) {
          $redirect_entity = $this->storage->load($entity->getEntityTypeId() . '__' . ContentTranslationRedirectForm::ALL_BUNDLES_KEY);
        }
        if ($redirect_entity) {
          // Get current path.
          $url = Url::fromRoute('<current>')->setAbsolute();
          $current_path = $url->toString();
          // Get redirect path to page in original language.
          $original_language = $entity->getUntranslated()->language();
          $url->setOption('language', $original_language);
          $redirect_path = $url->toString();

          // Redirect if the current path is not equal to the redirection path.
          if ($redirect_path != $current_path) {
            $status_code = $redirect_entity->getStatusCode() ?: $this->config->get('code');
            $message = $redirect_entity->getMessage() ?: $this->config->get('message');

            $response = new LocalRedirectResponse($redirect_path, $status_code);
            $response->addCacheableDependency($url);
            $event->setResponse($response);

            // Show warning message.
            if ($message) {
              // Get translated language name.
              $language_name = $this->languageManager->getLanguageName($current_language->getId());
              // Give the opportunity to display the name of the language
              // for which there is no translation.
              $message = new FormattableMarkup($message, ['%language' => $language_name]);
              $this->messenger()->addWarning($message);
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequestCheckRedirect'];
    return $events;
  }

  /**
   * Check that the current route is the content entity canonical route.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|bool
   *   The entity instance. FALSE if no entity is matched.
   */
  protected function checkContentEntityCanonicalRoute() {
    // Get current route name from route match.
    $route_name = $this->routeMatch->getRouteName();
    // If no route is matched.
    if (empty($route_name)) {
      return FALSE;
    }

    // Trying to find the Entity Type ID in the route parameters.
    foreach ($this->routeMatch->getParameters() as $parameter) {
      if ($parameter instanceof ContentEntityInterface) {
        try {
          // Compare entity canonical URL with current URL.
          $entity_route_name = $parameter->toUrl()->getRouteName();
          if ($entity_route_name == $route_name) {
            return $parameter;
          }
        }
        catch (\Exception $e) {
          // There is no canonical URL for this entity,
          // proceed to the next entity.
          continue;
        }
      }
    }
    return FALSE;
  }

}
