<?php

namespace Drupal\responsive_menu\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;

/**
 * Form builder for the responsive_menu admin settings page.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
   */
  protected $entityTypeManager;

  /**
   * Stored configuration for the module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   * @noinspection PhpFullyQualifiedNameUsageInspection
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config_factory->get('responsive_menu.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'responsive_menu_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['responsive_menu.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['responsive_menu'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Responsive menu'),
    ];
    $form['responsive_menu']['horizontal_menu'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose which Drupal menu will be rendered as a horizontal menu at the breakpoint width'),
      '#default_value' => $this->config->get('horizontal_menu'),
      '#options' => $this->getMenuOptions(),
      '#description' => $this->t("This menu will only be rendered if you use the included 'Horizontal menu' block. You can safely ignore this setting if you want to use another menu block instead."),
    ];
    $form['responsive_menu']['horizontal_depth'] = [
      '#type' => 'select',
      '#title' => $this->t('A maximum menu depth that the horizontal menu should display'),
      '#default_value' => $this->config->get('horizontal_depth'),
      '#options' => array_combine(
        [1, 2, 3, 4, 5, 6, 7, 8, 9],
        [1, 2, 3, 4, 5, 6, 7, 8, 9]
      ),
      '#description' => $this->t('The mobile menu will always allow all depths to be navigated to. This only controls what menu depth you want to display on the horizontal menu. It can be useful if you want a single row of items because you are handling secondary level and lower in a separate block.'),
    ];
    $form['responsive_menu']['off_canvas'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Off canvas'),
    ];
    $form['responsive_menu']['off_canvas']['off_canvas_menus'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter the name(s) of Drupal menus to be rendered in an off-canvas menu'),
      '#description' => $this->t('Enter the names of menus in a comma delimited format. If more than one menu is entered the menu items will be merged together. This is useful if you have a main menu and a utility menu that display separately at wider screen sizes but should be merged into a single menu at smaller screen sizes. Note that the menus will be merged in the entered order.'),
      '#default_value' => $this->config->get('off_canvas_menus'),
    ];
    $form['responsive_menu']['horizontal_wrapping_element'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose the HTML element to wrap the menu block in'),
      '#default_value' => $this->config->get('horizontal_wrapping_element'),
      '#options' => [
        'nav' => 'nav',
        'div' => 'div',
      ],
    ];
    $form['responsive_menu']['use_breakpoint'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use a breakpoint'),
      '#description' => $this->t("Unchecking this will disable the breakpoint and your mobile menu icon block will always display (assuming you have placed it on the page). This can be useful if you always want to display the mobile menu icon and don't want a horizontal menu at all, or if you want to control the visibility and breakpoints in your theme's css. Note that the horizontal menu block, if placed, will only be visible if this is checked."),
      '#default_value' => $this->config->get('use_breakpoint'),
    ];
    // Breakpoints.
    $queries = responsive_menu_get_breakpoints();
    $form['responsive_menu']['horizontal_breakpoint'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose a breakpoint to trigger the desktop format menu at'),
      '#default_value' => $this->config->get('horizontal_breakpoint'),
      '#options' => $queries,
      '#states' => [
        'visible' => [
          ':input[name="use_breakpoint"]' => ['checked' => TRUE],
        ],
      ],
      '#description' => $this->t('The mobile icon will be hidden at this breakpoint and the horizontal menu will show (if the block has been placed).'),
    ];
    if (empty($queries)) {
      $form['responsive_menu']['horizontal_breakpoint']['#disabled'] = TRUE;
      $form['responsive_menu']['horizontal_breakpoint']['#description'] = '<div class="description">' . $this->t('You must configure at least one @breakpoint to see any options. Until then the select widget above is disabled.', [
        '@breakpoint' => Link::fromTextAndUrl('breakpoint', Url::fromUri('https://www.drupal.org/documentation/modules/breakpoint'))->toString(),
      ]) . '</div>';

    }
    // Whether to load the base css.
    $form['responsive_menu']['css'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Load the responsive_menu module's css"),
      '#description' => $this->t('It might be that you want to override all of the css that comes with the responsive_menu module in which case you can disable the loading of the css here and include it instead in your theme.'),
      '#default_value' => $this->config->get('include_css'),
    ];
    // Whether to allow on admin pages.
    $form['responsive_menu']['allow_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow on the admin theme'),
      '#description' => $this->t('By default the mmenu library is not added to admin pages using the admin theme (if different). By checking this option the code which adds the javascript and the wrapping elements to the page will be added to every page including backend admin pages using the admin theme.'),
      '#default_value' => $this->config->get('allow_admin'),
    ];
    // Whether to add a theme wrapper for the admin theme.
    $form['responsive_menu']['wrapper_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add a page wrapper div to the admin theme'),
      '#description' => $this->t('Many admin themes do not have a wrapping div around all their regions (Seven theme for example) and mmenu requires this div to render properly. Checking this option will add the wrapping div using a preprocess hook.'),
      '#default_value' => $this->config->get('wrapper_admin'),
      '#states' => [
        // Only show this field when the 'allow_admin' checkbox is enabled.
        'visible' => [
          ':input[name="allow_admin"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['responsive_menu']['theme_compatibility'] = [
      '#type' => 'fieldset',
      '#title' => 'Theme compatiblity',
    ];
    $form['responsive_menu']['theme_compatibility']['use_bootstrap'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable compatibility mode for Bootstrap 4 themes'),
      '#description' => $this->t("Enabling this setting will override the Bootstrap 4 navbar menu icon so that it opens the off-canvas menu at the desired breakpoint instead of the Bootstrap navbar mobile menu. This will only work if the Bootstrap menu icon is within an element with the css ID #navbar-main, which is the default if using bootstrap_bario theme. See the README.md for more detail."),
      '#default_value' => $this->config->get('use_bootstrap'),
    ];
    // Whether to add a theme wrapper for the front end theme.
    $form['responsive_menu']['theme_compatibility']['wrapper_theme'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add a page wrapper div to the front end theme'),
      '#description' => $this->t("Some themes don't have a wrapping div around all their regions (Bootstrap theme for example) and mmenu requires this div to render properly. Checking this option will add the wrapping div using a preprocess hook. Alternatively you can do this manually in your theme."),
      '#default_value' => $this->config->get('wrapper_theme'),
    ];
    $form['responsive_menu']['theme_compatibility']['use_polyfills'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include IE11 polyfills'),
      '#description' => $this->t("If your theme needs to support IE11 then you should check this to load the mmenu polyfills needed for the mmenu library to work with IE11."),
      '#default_value' => $this->config->get('use_polyfills'),
    ];

    // Left or right positioned panel.
    $form['responsive_menu']['position'] = [
      '#type' => 'select',
      '#options' => [
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
        // To switch left/right position based on the language.
        'contextual' => $this->t('Contextual'),
      ],
      '#title' => $this->t("Which side the mobile menu panel should slide out from. Choose the 'Contextual' option to have the menu slide out from the left for LTR languages and from the right for RTL languages."),
      '#default_value' => $this->config->get('off_canvas_position'),
    ];
    // The theme of the slideout panel.
    $form['responsive_menu']['theme'] = [
      '#type' => 'select',
      '#options' => [
        'theme-light' => $this->t('Light'),
        'theme-dark' => $this->t('Dark'),
        'theme-black' => $this->t('Black'),
        'theme-white' => $this->t('White'),
      ],
      '#title' => $this->t('Which mmenu theme to use'),
      '#default_value' => $this->config->get('off_canvas_theme'),
    ];
    // Whether to dim to the page when the menu slides out.
    $form['responsive_menu']['pagedim'] = [
      '#type' => 'select',
      '#options' => [
        'none' => $this->t('No page dim'),
        'pagedim' => $this->t('Dim to default page colour'),
        'pagedim-white' => $this->t('Dim to white'),
        'pagedim-black' => $this->t('Dim to black'),
      ],
      '#title' => $this->t('The colour to dim the page to when the menu slides out'),
      '#default_value' => $this->config->get('pagedim'),
    ];
    // Chrome has a problem with displaying the mmenu correctly at mobile widths
    // unless a specific viewport value is provided.
    $form['responsive_menu']['modify_viewport'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Dynamically modify the viewport meta tag'),
      '#default_value' => $this->config->get('modify_viewport'),
      '#description' => $this->t("Chrome has an issue displaying the off-canvas menu correctly unless a specific viewport meta tag value is provided. Checking this will leave your theme's viewport meta tag as it is until the off-canvas menu is opened at which point it will use an optimised value (width=device-width, initial-scale=1.0, minimum-scale=1.0)"),
    ];
    // A javascript enhancements fieldset.
    $form['responsive_menu']['js'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Javascript enhancements'),
    ];
    $form['responsive_menu']['js']['superfish'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Apply Superfish to the horizontal menu'),
      '#description' => $this->t('Adds the <a href="@superfish">Superfish</a> library functionality to the horizontal menu. This enhances the menu with better support for hovering and support for mobiles.', [
        '@superfish' => 'https://github.com/joeldbirch/superfish',
      ]),
      '#default_value' => $this->config->get('horizontal_superfish'),
    ];
    $form['responsive_menu']['js']['superfish_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Superfish options'),
      '#states' => [
        'visible' => [
          ':input[name="superfish"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['responsive_menu']['js']['superfish_options']['superfish_delay'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Delay'),
      '#description' => $this->t('The amount of time in milliseconds a menu will remain after the mouse leaves it.'),
      '#default_value' => $this->config->get('horizontal_superfish_delay'),
    ];
    $form['responsive_menu']['js']['superfish_options']['superfish_speed'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Speed'),
      '#description' => $this->t('The amount of time in milliseconds it takes for a menu to reach 100% opacity when it opens.'),
      '#default_value' => $this->config->get('horizontal_superfish_speed'),
    ];
    $form['responsive_menu']['js']['superfish_options']['superfish_speed_out'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Speed out'),
      '#description' => $this->t('The amount of time in milliseconds it takes for a menu to reach 0% opacity when it closes.'),
      '#default_value' => $this->config->get('horizontal_superfish_speed_out'),
    ];
    $form['responsive_menu']['js']['superfish_options']['superfish_hoverintent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use hoverintent'),
      '#description' => $this->t('Whether to use the <a href="@hoverintent">HoverIntent</a> plugin with superfish. This library is included in the superfish download and doesn\'t require separate installation.', [
        '@hoverintent' => 'http://cherne.net/brian/resources/jquery.hoverIntent.html',
      ]),
      '#default_value' => $this->config->get('horizontal_superfish_hoverintent'),
    ];
    // Whether the optional superfish library is to be used.
    if (!file_exists(DRUPAL_ROOT . '/libraries/superfish/dist/js/superfish.min.js')) {
      $form['responsive_menu']['js']['superfish']['#disabled'] = TRUE;
      $form['responsive_menu']['js']['superfish']['#description'] .= '<br/><span class="warning">' . $this->t('You need to download the <a href="@superfish">Superfish</a> library and place it in a /libraries directory. Until then the superfish option is disabled.', [
        '@superfish' => 'https://github.com/joeldbirch/superfish/archive/master.zip',
      ]) . '</span>';
    }
    // The 'drag' or swipe gesture enhancement for mobile users.
    $form['responsive_menu']['js']['drag'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add drag to open gesture'),
      '#description' => $this->t('Enhance the mobile experience with swipe/drag gesture to open the menu.'),
      '#default_value' => $this->config->get('drag'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Ensure there are breakpoints configured.
    $values = $form_state->getValues();
    if ($values['use_breakpoint'] && empty($values['horizontal_breakpoint'])) {
      $breakpoint_message = Link::fromTextAndUrl('breakpoint file', Url::fromUri('https://www.drupal.org/node/1803874'))->toRenderable();
      $form_state->setErrorByName('horizontal_breakpoint', $this->t("You have chosen to use a breakpoint but you have not selected one. This may happen if your @breakpoint is not properly set up.", [
        '@breakpoint' => render($breakpoint_message),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    // Save all the submitted form values into config.
    $this->configFactory
      ->getEditable('responsive_menu.settings')
      ->set('horizontal_menu', $values['horizontal_menu'])
      ->set('horizontal_depth', $values['horizontal_depth'])
      ->set('horizontal_wrapping_element', $values['horizontal_wrapping_element'])
      ->set('use_breakpoint', $values['use_breakpoint'])
      ->set('include_css', $values['css'])
      ->set('allow_admin', $values['allow_admin'])
      ->set('use_bootstrap', $values['use_bootstrap'])
      ->set('wrapper_admin', $values['wrapper_admin'])
      ->set('wrapper_theme', $values['wrapper_theme'])
      ->set('use_polyfills', $values['use_polyfills'])
      ->set('pagedim', $values['pagedim'])
      ->set('modify_viewport', $values['modify_viewport'])
      ->set('off_canvas_menus', $values['off_canvas_menus'])
      ->set('off_canvas_position', $values['position'])
      ->set('off_canvas_theme', $values['theme'])
      ->set('horizontal_superfish', $values['superfish'])
      ->set('horizontal_superfish_delay', $values['superfish_delay'])
      ->set('horizontal_superfish_speed', $values['superfish_speed'])
      ->set('horizontal_superfish_speed_out', $values['superfish_speed_out'])
      ->set('horizontal_superfish_hoverintent', $values['superfish_hoverintent'])
      ->set('drag', $values['drag'])
      ->save();

    // Handle the breakpoint.
    $queries = responsive_menu_get_breakpoints();
    // Check if the breakpoint exists and the user has chosen
    // to use a breakpoint.
    if ($values['use_breakpoint'] && isset($queries[$values['horizontal_breakpoint']])) {
      // Store the breakpoint for using again in the form.
      $this->configFactory
        ->getEditable('responsive_menu.settings')
        ->set('horizontal_breakpoint', $values['horizontal_breakpoint'])
        // Also store the actual breakpoint string for use in calling
        // the stylesheet.
        ->set('horizontal_media_query', $queries[$values['horizontal_breakpoint']])
        ->save();

      // Generate the breakpoint css file and remove existing one.
      $path = _get_breakpoint_css_filepath();
      // Ensure the directory exists, if not create it.
      if (file_exists($path . RESPONSIVE_MENU_BREAKPOINT_FILENAME)) {
        unlink($path . RESPONSIVE_MENU_BREAKPOINT_FILENAME);
      }
      $breakpoint = $this->config->get('horizontal_media_query');
      responsive_menu_generate_breakpoint_css($breakpoint);
    }

    // Invalidate the offcanvas preRender result so these settings get
    // applied when rebuilding the page. Fixes #3134331.
    Cache::invalidateTags(['offcanvas_render', 'horizontal_menu']);

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets a list of menu names for use as options.
   *
   * @param array $menu_names
   *   (optional) Array of menu names to limit the options, or NULL to load all.
   *
   * @return array
   *   Keys are menu names (ids) values are the menu labels.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @noinspection PhpFullyQualifiedNameUsageInspection
   */
  protected function getMenuOptions(array $menu_names = NULL) {
    $menus = $this->entityTypeManager->getStorage('menu')->loadMultiple($menu_names);
    $options = [];
    /** @var \Drupal\system\MenuInterface[] $menus */
    foreach ($menus as $menu) {
      $options[$menu->id()] = $menu->label();
    }
    return $options;
  }

}
