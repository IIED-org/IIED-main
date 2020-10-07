<?php

namespace Drupal\sidr\Plugin\Block;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a trigger button with Sidr integration.
 *
 * @Block(
 *   id = "sidr_trigger",
 *   admin_label = @Translation("Sidr trigger button block"),
 * )
 */
class SidrTrigger extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'trigger_text' => '',
      'trigger_icon' => '',
      'sidr_name' => '',
      'sidr_source' => '',
      'sidr_side' => 'left',
      'sidr_renaming' => FALSE,
      'sidr_nocopy' => FALSE,
      'sidr_speed' => '',
      'sidr_timing' => '',
      'sidr_method' => 'toggle',
      'sidr_displace' => '',
      'sidr_body' => '',
      'sidr_close_on_blur' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'sidr_trigger',
      // TODO: Why can't we directly set these options in attributes?
      '#options' => $this->getSidrJsOptions(),
      '#configuration' => $this->configuration,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $conf = $this->getConfiguration();
    $settings = $this->configFactory->get('sidr.settings');

    // Basic settings.
    $form['basic'] = [
      '#title' => $this->t('Basic settings'),
      '#type' => 'fieldset',
    ];
    $form['basic']['trigger_text'] = [
      '#title' => $this->t('Trigger text'),
      '#type' => 'textfield',
      '#description' => $this->t('Text to display on the trigger. Example: @example', [
        '@example' => 'Menu',
      ]),
      '#rows' => 3,
      '#maxlength' => 255,
      '#default_value' => $conf['trigger_text'],
    ];
    $form['basic']['sidr_source'] = [
      '#title' => $this->t('Source'),
      '#type' => 'textarea',
      '#description' => $this->t('A jQuery selector, a URL or a callback function.'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#default_value' => $conf['sidr_source'],
    ];
    $form['basic']['sidr_side'] = [
      '#title' => $this->t('Location'),
      '#type' => 'radios',
      '#options' => [
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
      ],
      '#default_value' => $conf['sidr_side'],
    ];
    $form['basic']['theme'] = [
      '#title' => 'Theme',
      '#type' => 'textfield',
      '#description' => $this->t('To modify the global sidr theme, visit the <a href="@sidr-settings">Sidr settings</a> page.', [
        '@sidr-settings' => Url::fromRoute('sidr.settings')->toString(),
      ]),
      '#disabled' => TRUE,
      '#default_value' => $settings->get('sidr_theme'),
    ];

    // Advanced settings.
    $form['advanced'] = [
      '#title' => $this->t('Advanced settings'),
      '#type' => 'details',
      '#description' => $this->t('For more information about various Sidr options, see the <a href="@sidr-documentation">Sidr documentation</a> page.', [
        '@sidr-documentation' => 'https://www.berriart.com/sidr/',
      ]),
      '#open' => FALSE,
    ];
    $form['advanced']['trigger_icon'] = [
      '#title' => $this->t('Trigger icon'),
      '#type' => 'textarea',
      '#description' => $this->t('Icon to display on the trigger. Example: @example', [
        '@example' => '<span class="icon-hamburger"></span>',
      ]),
      '#maxlength' => 255,
      '#default_value' => $conf['trigger_icon'],
    ];
    $form['advanced']['sidr_name'] = [
      '#title' => $this->t('Unique ID'),
      '#type' => 'textfield',
      '#description' => $this->t('A unique DOM ID for the sidr instance. Example: @example', [
        '@example' => 'sidr-left',
      ]),
      '#maxlength' => 255,
      '#default_value' => $conf['sidr_name'],
    ];
    $form['advanced']['sidr_method'] = [
      '#title' => $this->t('Trigger action'),
      '#type' => 'select',
      '#options' => [
        'toggle' => $this->t('Toggle'),
        'open' => $this->t('Open'),
        'close' => $this->t('Close'),
      ],
      '#default_value' => $conf['sidr_method'],
    ];
    $form['advanced']['sidr_speed'] = [
      '#title' => $this->t('Animation speed'),
      '#type' => 'textfield',
      '#description' => $this->t('Examples: @example', [
        '@example' => 'slow, fast, 400',
      ]),
      '#default_value' => $conf['sidr_speed'],
    ];
    $form['advanced']['sidr_timing'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Animation timing function'),
      '#description' => $this->t('Examples: @example', [
        '@example' => 'linear, ease, cubic-bezier(...)',
      ]),
      '#maxlength' => 32,
      '#default_value' => $conf['sidr_timing'],
    ];
    $form['advanced']['sidr_nocopy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable duplication?'),
      '#description' => $this->t('<a href=":issue-url" target="_blank">Use original source elements</a> in the Sidr panel instead of copying their inner HTML (recommended).', [
        ':issue-url' => 'https://github.com/artberri/sidr/issues/339',
      ]),
      '#default_value' => $conf['sidr_nocopy'],
      '#states' => [
        'disabled' => [
          ':input[name="settings[advanced][sidr_renaming]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['advanced']['sidr_renaming'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rename elements?'),
      '#description' => $this->t('Rename classes and IDs of source elements when filling the Sidr with existing content.'),
      '#default_value' => $conf['sidr_renaming'],
      '#states' => [
        'disabled' => [
          ':input[name="settings[advanced][sidr_nocopy]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['advanced']['sidr_displace'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Displace content?'),
      '#description' => $this->t('Whether to displace page content during open and close animations.'),
      '#default_value' => $conf['sidr_displace'],
    ];
    $form['advanced']['sidr_body'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Element to displace'),
      '#description' => $this->t('The element to be displaced during open / close animations instead of the @body element.', [
        '@body' => 'BODY',
      ]),
      '#default_value' => $conf['sidr_body'],
      '#maxlength' => 255,
      '#states' => [
        'visible' => [
          ':input[name="settings[advanced][sidr_displace]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    parent::blockValidate($form, $form_state);
    $values = $form_state->getValues();
    $values = $values['basic'] + $values['advanced'];
    // Either trigger text or trigger icon must be set.
    if (!$values['trigger_text'] && !$values['trigger_icon']) {
      $message = $this->t('Please provide either trigger text or a trigger icon.');
      $form_state->setError($form['basic']['trigger_text'], $message);
      $form_state->setError($form['advanced']['trigger_icon'], $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $values = $values['basic'] + $values['advanced'];
    $keys = array_keys($this->defaultConfiguration());
    foreach ($keys as $key) {
      switch ($key) {
        case 'sidr_renaming':
        case 'sidr_displace':
        case 'sidr_nocopy':
          $this->configuration[$key] = (bool) $values[$key];
          break;

        default:
          $this->configuration[$key] = $values[$key];
      }
    }
  }

  /**
   * Returns block configuration as options for the Sidr jQuery plugin.
   *
   * @return array
   *   Sidr options.
   */
  protected function getSidrJsOptions() {
    $conf = $this->getConfiguration();
    $output = [
      'source' => $conf['sidr_source'],
      'name' => $conf['sidr_name'],
      'side' => $conf['sidr_side'],
      'method' => $conf['sidr_method'],
      'speed' => $conf['sidr_speed'],
      'timing' => is_numeric($conf['sidr_timing']) ? (int) $conf['sidr_timing'] : $conf['sidr_timing'],
      'renaming' => $conf['sidr_renaming'],
      'displace' => $conf['sidr_displace'],
      'nocopy' => $conf['sidr_nocopy'],
      'body' => $conf['sidr_displace'] ? $conf['sidr_body'] : '',
    ];
    // TODO: Require PHP 5.3 and use anonymous callback.
    return array_filter($output, [__CLASS__, 'isOptionNonEmpty']);
  }

  /**
   * Test whether a Sidr option is not empty.
   *
   * @param mixed $value
   *   The value to test.
   *
   * @return bool
   *   TRUE if the value is non-empty.
   */
  public static function isOptionNonEmpty($value) {
    return is_bool($value) || !empty($value);
  }

}
