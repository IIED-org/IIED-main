<?php

namespace Drupal\datalayer\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\AdminContext;

/**
 * Provides page hook implementations for the datalayer module.
 */
class DatalayerPageHooks {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The admin context.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * Constructs a new DatalayerPageHooks object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The admin context.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LanguageManagerInterface $language_manager,
    AdminContext $admin_context,
  ) {
    $this->configFactory = $config_factory;
    $this->languageManager = $language_manager;
    $this->adminContext = $admin_context;
  }

  /**
   * Implements hook_page_attachments().
   *
   * Load all meta tags for this page.
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments) {
    $datalayer_settings = $this->configFactory->get('datalayer.settings');
    // Optionally do not run dataLayer code on admin pages.
    if ($this->adminContext->isAdminRoute() && $datalayer_settings->get('remove_from_admin_routes') === TRUE) {
      return;
    }
    if (empty($attachments['#attached'])) {
      $attachments['#attached'] = [];
    }
    // Include data-layer-helper library.
    if ($datalayer_settings->get('lib_helper')) {
      $attachments['#attached']['library'][] = 'datalayer/helper';
    }
    // Output configured language data.
    $languages = $this->languageManager->getLanguages();
    if (count($languages)) {
      $langs = [];
      foreach ($languages as $id => $language) {
        $langs[$id] = [
          'id' => $id,
          'name' => $language->getName(),
          'direction' => $language->getDirection(),
          'weight' => $language->getWeight(),
        ];
        if ($language->isDefault()) {
          $attachments['#attached']['drupalSettings']['dataLayer']['defaultLang'] = $id;
        }
      }
      $attachments['#attached']['drupalSettings']['dataLayer']['languages'] = $langs;
      $attachments['#cache']['contexts'][] = 'languages:language_content';
    }
    // Common datalayer JS.
    $attachments['#attached']['library'][] = 'datalayer/behaviors';
  }

  /**
   * Implements hook_page_bottom().
   */
  #[Hook('page_bottom')]
  public function pageBottom(array &$page_bottom) {
    $page_bottom['datalayer'] = [
      '#lazy_builder' => [
        'datalayer.lazy_builders:lazyScriptTag',
              [],
      ],
      '#create_placeholder' => TRUE,
      '#cache' => [
        'contexts' => [
          'user',
        ],
      ],
    ];
  }

}
