<?php

namespace Drupal\tfa\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\Plugin\Block\UserLoginBlock;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Tfa User login' block.
 *
 * @Block(
 *   id = "tfa_user_login_block",
 *   admin_label = @Translation("Tfa User login"),
 *   category = @Translation("Forms")
 * )
 */
class TfaUserLoginBlock extends UserLoginBlock {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new UserLoginBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_match);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $access = parent::blockAccess($account);
    $tfaAccess = $this->configFactory->get('tfa.settings')->get('enabled');

    $route_name = $this->routeMatch->getRouteName();
    $disabled_route = in_array($route_name, ['tfa.entry']);
    if ($access->isForbidden() || !$tfaAccess || $disabled_route) {
      return AccessResult::forbidden();
    }
    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\tfa\Form\TfaLoginForm');
    unset($form['name']['#attributes']['autofocus']);
    // When unsetting field descriptions, also unset aria-describedby attributes
    // to avoid introducing an accessibility bug.
    // @todo Do this automatically in https://www.drupal.org/node/2547063.
    unset($form['name']['#description']);
    unset($form['name']['#attributes']['aria-describedby']);
    unset($form['pass']['#description']);
    unset($form['pass']['#attributes']['aria-describedby']);
    $form['name']['#size'] = 15;
    $form['pass']['#size'] = 15;
    $form['#action'] = Url::fromRoute('<current>', [], [
      'query' => $this->getDestinationArray(),
      'external' => FALSE,
    ])->toString();
    // Build action links.
    $items = [];
    if ($this->configFactory->get('user.settings')->get('register') != UserInterface::REGISTER_ADMINISTRATORS_ONLY) {
      $items['create_account'] = Link::fromTextAndUrl($this->t('Create new account'), new Url('user.register', [], [
        'attributes' => [
          'title' => $this->t('Create a new user account.'),
          'class' => ['create-account-link'],
        ],
      ]));
    }
    $items['request_password'] = Link::fromTextAndUrl($this->t('Reset your password'), new Url('user.pass', [], [
      'attributes' => [
        'title' => $this->t('Send password reset instructions via email.'),
        'class' => ['request-password-link'],
      ],
    ]));
    return [
      'user_login_form' => $form,
      'user_links' => [
        '#theme' => 'item_list',
        '#items' => $items,
      ],
    ];
  }

}
