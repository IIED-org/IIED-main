<?php

namespace Drupal\language_switcher_extended;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;

/**
 * Processes the Language Switcher links.
 */
class LanguageSwitcherLinkProcessor {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * LanguageSwitcherLinkProcessor constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   Route match service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, CurrentRouteMatch $currentRouteMatch, LanguageManagerInterface $language_manager) {
    $this->configFactory = $configFactory;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->languageManager = $language_manager;
  }

  /**
   * Processes the language switcher links.
   *
   * @param array $links
   *   Language switcher links to be processed.
   */
  public function process(array &$links) {
    $config = $this->configFactory->get('language_switcher_extended.settings');

    switch ($config->get('mode')) {
      case 'always_link_to_front':
        $this->processAlwaysLinkToFrontpage($links);
        break;

      case 'process_untranslated':
        $this->processUntranslatedLinks($links);
        break;
    }

    // If there's no more links after the processing there's nothing more to do.
    if (empty($links)) {
      return;
    }

    // This will run if the mode is not "core/default".
    switch ($config->get('current_language_mode')) {
      case 'hide_link':
        $this->hideCurrentLanguage($links);
        break;

      case 'no_link':
        $this->hideCurrentLanguageLink($links);
        break;
    }

    // Show language instead of language name.
    if ($config->get('show_langcode')) {
      $this->showLanguageCode($links);
    }
  }

  /**
   * Shows the langcode instead of language names.
   *
   * @param array $links
   *   Language switcher links to be processed.
   */
  protected function showLanguageCode(array &$links) {
    foreach ($links as $langcode => $link) {
      $links[$langcode]['title'] = $langcode;
    }
  }

  /**
   * Links all language switcher items to their corresponding frontpage.
   *
   * @param array $links
   *   Language switcher links to be processed.
   */
  protected function processAlwaysLinkToFrontpage(array &$links) {
    foreach ($links as $langcode => $link) {
      $links[$langcode]['url'] = new Url('<front>');
    }
  }

  /**
   * Processes all untranslated language switcher items.
   *
   * @param array $links
   *   Language switcher links to be processed.
   */
  protected function processUntranslatedLinks(array &$links) {
    $config = $this->configFactory->get('language_switcher_extended.settings');

    if ($entity = $this->getPageEntity()) {
      $untranslatedHandler = $config->get('untranslated_handler');

      foreach ($links as $langcode => $link) {
        if (!$entity->hasTranslation($langcode) || !$entity->getTranslation($langcode)
          ->access('view')) {
          switch ($untranslatedHandler) {
            case 'hide_link':
              unset($links[$langcode]);
              break;

            case 'link_to_front':
              $links[$langcode]['url'] = new Url('<front>');
              break;

            case 'no_link':
              unset($links[$langcode]['url']);
              $links[$langcode]['attributes']['class'][] = 'language-link--untranslated';
              break;
          }
        }
      }

      // Hides the links, if we have only a single language switcher item left.
      if ($config->get('hide_single_link') && count($links) < 2) {
        $links = $config->get('hide_single_link_block') ? NULL : [];
      }

    }
  }

  /**
   * Hides the link to the current language.
   *
   * @param array $links
   *   Language switcher links to be processed.
   */
  protected function hideCurrentLanguage(array &$links) {
    $currentLanguage = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();

    // Remove the links to the current language.
    unset($links[$currentLanguage]);
  }

  /**
   * Display the current language without link.
   *
   * @param array $links
   *   Language switcher links to be processed.
   */
  protected function hideCurrentLanguageLink(array &$links) {
    $currentLanguage = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();

    // Display the current language without link.
    unset($links[$currentLanguage]['url']);
    $links[$currentLanguage]['attributes']['class'][] = 'is-active';
  }

  /**
   * Retrieves the current page entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The retrieved entity, or FALSE if none found.
   */
  protected function getPageEntity() {
    $params = $this->currentRouteMatch->getParameters()->all();
    // Iterate through the parameters and search for the first content entity.
    // Use a loop, because the content entity might not be the first parameter,
    // e.g. when using page_manager.
    foreach ($params as $param) {
      if ($param instanceof ContentEntityInterface) {
        // If you find a ContentEntityInterface stop iterating and return it.
        return $param;
      }
    }
    return FALSE;
  }

}
