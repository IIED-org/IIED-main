<?php

namespace Drupal\tfa\Plugin\views\field;

use Drupal\Component\Utility\Xss as UtilityXss;
use Drupal\user\UserDataInterface;
use Drupal\views\Plugin\views\field\Boolean;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a views field to show if the selected user has enabled TFA.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("tfa_enabled_field")
 */
class TfaEnabledField extends Boolean {

  /**
   * Provides the user data service object.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('user.data'));
  }

  /**
   * Constructs a UserData object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\user\UserDataInterface $user_data
   *   User data object to store user specific information.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserDataInterface $user_data) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->userData = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $uid = $this->getValue($values);
    $data = $this->userData->get('tfa', $uid, 'tfa_user_settings');
    $value = $data['saved'] ?? FALSE;

    if ($this->options['type'] == 'custom') {
      $custom_value = $value ? $this->options['type_custom_true'] : $this->options['type_custom_false'];
      return ViewsRenderPipelineMarkup::create(UtilityXss::filterAdmin($custom_value));
    }
    elseif (isset($this->formats[$this->options['type']])) {
      return $value ? $this->formats[$this->options['type']][0] : $this->formats[$this->options['type']][1];
    }
    else {
      return $value ? $this->formats['yes-no'][0] : $this->formats['yes-no'][1];
    }
  }

}
