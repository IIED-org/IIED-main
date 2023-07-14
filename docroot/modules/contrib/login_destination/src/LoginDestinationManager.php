<?php

namespace Drupal\login_destination;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\login_destination\Entity\LoginDestination;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a login destination manager service.
 */
class LoginDestinationManager implements LoginDestinationManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The alias manager that caches alias lookups based on the request.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The alias manager.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AliasManagerInterface $alias_manager, PathMatcherInterface $path_matcher, CurrentPathStack $current_path, ConfigFactoryInterface $config_factory, RequestStack $request_stack, LanguageManagerInterface $language_manager, Token $token) {
    $this->entityTypeManager = $entity_type_manager;
    $this->aliasManager = $alias_manager;
    $this->pathMatcher = $path_matcher;
    $this->currentPath = $current_path;
    $this->configFactory = $config_factory;
    $this->requestStack = $request_stack;
    $this->languageManager = $language_manager;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public function findDestination($trigger, AccountInterface $account) {
    $destinations = $this->entityTypeManager->getStorage('login_destination')
      ->loadMultiple();
    uasort($destinations, '\Drupal\login_destination\Entity\LoginDestination::sort');

    $path = $this->getCurrentPath();
    try {
      $path_alias = mb_strtolower($this->aliasManager->getAliasByPath($path));
    }
    catch (\InvalidArgumentException $e) {
      // Cannot match invalid paths.
      $path_alias = NULL;
    }

    // Get user roles.
    $user_roles = $account->getRoles();

    /** @var \Drupal\login_destination\Entity\LoginDestination $destination */
    foreach ($destinations as $destination) {

      if (!$destination->isEnabled()) {
        continue;
      }

      // Determine if the trigger matches that of the login destination rule.
      $destination_triggers = $destination->getTriggers();
      if (!in_array($trigger, $destination_triggers)) {
        continue;
      }

      $destination_roles = $destination->getRoles();

      $role_match = array_intersect($user_roles, $destination_roles);
      // Ensure that the user logging in has a role allowed by the login
      // destination rule and the login destination rule does not have any
      // selected roles.
      if (empty($role_match) && !empty($destination_roles)) {
        continue;
      }

      $destination_language = $destination->getLanguage();
      $lang_code = $this->languageManager->getCurrentLanguage()->getId();
      if ($destination_language != '' && $destination_language != $lang_code) {
        continue;
      }

      $pages = mb_strtolower($destination->getPages());
      if (!empty($pages)) {
        $type = $destination->getPagesType();
        $page_match = $this->pathMatcher->matchPath($path_alias, $pages) || $this->pathMatcher->matchPath($path, $pages);

        // Make sure the page matches(or does not match if the rule specifies
        // that).
        if (($page_match && $type == $destination::REDIRECT_LISTED) || (!$page_match && $type == $destination::REDIRECT_NOT_LISTED)) {

          return $destination;
        }
        continue;
      }

      return $destination;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareDestination(LoginDestination $destination) {
    // Get config with settings.
    $config = $this->configFactory->get('login_destination.settings');
    if ($config->get('preserve_destination')) {
      // Get current destination value.
      $drupal_destination = $this->requestStack->getCurrentRequest()->query->get('destination');
      if ($drupal_destination && !UrlHelper::isExternal($drupal_destination)) {
        return;
      }
    }

    // Prepare destination path.
    $path = $this->token->replace($destination->getDestination());
    // Check if rules refers to the current page.
    if ($destination->isDestinationCurrent()) {
      $request = $this->requestStack->getCurrentRequest();
      $query = $request->get('current');
      $path_destination = !empty($query) ? $query : '';
    }
    else {
      $path_destination = Url::fromUri($path)->toString();
    }

    // Set destination to current request.
    $this->requestStack->getCurrentRequest()->query->set('destination', $path_destination);
  }

  /**
   * Get current path.
   *
   * @return string
   *   Returns the current path.
   */
  protected function getCurrentPath() {
    $current = $this->requestStack->getCurrentRequest()->get('current', '');
    if (empty($current)) {
      $current = $this->currentPath->getPath();
    }
    return $current;
  }

}
