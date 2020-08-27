<?php

namespace Drupal\dropdown_language\Plugin\Block;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an alternative language switcher block.
 *
 * @Block(
 *   id = "dropdown_language",
 *   admin_label = @Translation("Dropdown Language Selector"),
 *   category = @Translation("Custom Blocks"),
 *   deriver = "Drupal\dropdown_language\Plugin\Derivative\DropdownLanguage"
 * )
 */
class DropdownLanguage extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The Route Matcher.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new DropdownLanguage instance.
   *
   * @param array $block_configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route Matcher.
   */
  public function __construct(array $block_configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, PathMatcherInterface $path_matcher, RouteMatchInterface $route_match) {
    parent::__construct($block_configuration, $plugin_id, $plugin_definition);

    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
    $this->pathMatcher = $path_matcher;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $block_configuration, $plugin_id, $plugin_definition) {
    return new static(
      $block_configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('config.factory'),
      $container->get('path.matcher'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $access = $this->languageManager->isMultilingual() ? AccessResult::allowed() : AccessResult::forbidden();
    return $access->addCacheTags(['config:configurable_language_list']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [];
    $build = [];
    $languages = $this->languageManager->getLanguages();
    if (count($languages) > 1) {
      $derivative_id = $this->getDerivativeId();
      $route = $this->pathMatcher->isFrontPage() ? '<front>' : '<current>';
      $current_language = $this->languageManager->getCurrentLanguage($derivative_id)->getId();
      $links = $this->languageManager->getLanguageSwitchLinks($derivative_id, Url::fromRoute($route))->links;

      // Place active language ontop of list.
      if (isset($links[$current_language])) {
        $links = [$current_language => $links[$current_language]] + $links;
        // Set an active class for styling.
        $links[$current_language]['attributes']['class'][] = 'active-language';
        // Remove self-referencing link.
        $links[$current_language]['url'] = Url::fromRoute('<nolink>');
      }

      // Get block instance and general settings.
      $block_config = $this->getConfiguration();
      $general_config = $this->configFactory->get('dropdown_language.setting');
      $wrapper_default = $general_config->get('wrapper');
      $display_language_id = $general_config->get('display_language_id');
      $filter_untranslated = $general_config->get('filter_untranslated');
      $always_show_block = $general_config->get('always_show_block');

      // Only load once, rather than in switch (in a loop).
      $native_names = FALSE;
      if ($display_language_id == 2) {
        $native_names = $this->languageManager->getStandardLanguageList();
      }

      /**
       * Discover the entity we are currently viewing.
       * note:  page manager (and other) entities need routines. @v3 plugin.
      */
      $entity = FALSE;
      if ($filter_untranslated == '1') {
        $routedItems = $this->routeMatch;
        foreach ($routedItems->getParameters() as $param) {
          if ($param instanceof EntityInterface) {
            $entity['EntityInterface'] = $param;
          }
        }
      }

      foreach ($links as $lid => $link) {

        // Re-label as per general setting.
        switch ($display_language_id) {
          case '1':
            $links[$lid]['title'] = mb_strtoupper($lid);
            break;

          case '2':
            $name = $link['language']->getName();
            $links[$lid]['title'] = isset($native_names[$lid]) ? $native_names[$lid][1] : $name;
            if (isset($native_names[$lid]) && (isset($native_names[$lid]) && $native_names[$lid][1] != $name)) {
              $links[$lid]['attributes']['title'] = $name;
            }
            break;

          case '3':
            $links[$lid]['title'] = isset($block_config['labels'][$lid]) ? $block_config['labels'][$lid] : $link['language']->getName();
            break;
        }

        // Removes unused languages from the dropdown.
        if ($entity && $entity['EntityInterface'] && $filter_untranslated == '1') {
          $has_translation = (method_exists($entity['EntityInterface'], 'getTranslationStatus')) ? $entity['EntityInterface']->getTranslationStatus($lid) : FALSE;
          $this_translation = ($has_translation && method_exists($entity['EntityInterface'], 'getTranslation')) ? $entity['EntityInterface']->getTranslation($lid) : FALSE;
          $access_translation = ($this_translation && method_exists($this_translation, 'access') && $this_translation->access('view')) ? TRUE : FALSE;
          if (!$this_translation || !$access_translation) {
            unset($links[$lid]);
          }
        }
      }

      $dropdown_button = [
        '#type' => 'dropbutton',
        '#subtype' => 'dropdown_language',
        '#links' => $links,
        '#attributes' => [
          'class' => ['dropdown-language-item'],
        ],
        '#attached' => [
          'library' => ['dropdown_language/dropdown-language-selector'],
        ],
      ];
      if ($wrapper_default == 1) {
        $block['switcher'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Switch Language'),
        ];
        $block['switcher']['switch-language'] = $dropdown_button;
      }
      else {
        $block['switch-language'] = $dropdown_button;
      }
    }

    if (count($links) > 1 || $always_show_block) {
      $build['dropdown-language'] = $block;
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['#attached']['library'][] = 'dropdown_language/dropdown-language-admin';

    $general_config = $this->configFactory->get('dropdown_language.setting');
    $display_language_id = $general_config->get('display_language_id');

    $current_path = Url::fromRoute('<current>')->toString();
    $general_settings = [
      '#type' => 'link',
      '#title' => $this->t('General Settings for all Dropdown Language Blocks'),
      '#url' => Url::fromRoute('dropdown_language.setting', [
          ['destination' => $current_path],
      ]
      ),
      '#attributes' => [
        'class' => ['dropdown-general-settings'],
      ],
    ];

    if ($display_language_id == 3) {
      $block_config = $this->getConfiguration();
      $languages = $this->languageManager->getLanguages();
      $form['labels'] = [
        '#type' => 'details',
        '#title' => $this->t('Custom Labels for Language Names'),
        '#open' => TRUE,
        '#tree' => TRUE,
      ];
      foreach ($languages as $lid => $item) {
        $form['labels'][$lid] = [
          '#type' => 'textfield',
          '#required' => TRUE,
          '#title' => $this->t('Label for <q>@lng</q>', ['@lng' => $item->getName()]),
          '#default_value' => isset($block_config['labels'][$lid]) ? $block_config['labels'][$lid] : $item->getName(),
        ];
      }
      $form['labels']['translation-note'] = [
        '#type' => 'inline_template',
        '#template' => '<dl class="dropdown-language-help"><dt>{{ title }}</dt><dd>{{ text }}</dd></dl>',
        '#context' => [
          'title' => $this->t('How to translate custom labels'),
          'text' => $this->t('Create a unique block instance for each language then assign via Language Visibility per block.  This is due to the general idea that you are now changing labels that were otherwise normally translatable strings by using these Custom Labels.'),
        ],
      ];
      $form['labels']['setting-link'] = $general_settings;
    }
    else {
      $form['setting-link'] = $general_settings;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('labels', $form_state->getValue('labels'));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
