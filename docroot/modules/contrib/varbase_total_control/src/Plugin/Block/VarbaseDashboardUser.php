<?php

namespace Drupal\varbase_total_control\Plugin\Block;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Varbase Dashboard User'.
 *
 * @Block(
 * id = "varbase_dashboard_user",
 * admin_label = @Translation("Varbase Dashboard User"),
 * category = @Translation("Dashboard")
 * )
 */
class VarbaseDashboardUser extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * AccountProxy current user definition.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * Creates a VarbaseDashboardUser block instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   AccountProxy current user definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $user_storage
   *   The user storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TranslationInterface $string_translation, RedirectDestinationInterface $redirect_destination, AccountInterface $current_user, EntityStorageInterface $user_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->stringTranslation = $string_translation;
    $this->redirectDestination = $redirect_destination;
    $this->currentUser = $current_user;
    $this->userStorage = $user_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation'),
      $container->get('redirect.destination'),
      $container->get('current_user'),
      $container->get('entity.manager')->getStorage('user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $user = $this->userStorage->load($this->currentUser->id());
    $destination = $this->redirectDestination->getAsArray();
    $options = [
      $destination,
    ];

    $name = Link::fromTextAndUrl($user->getDisplayName(), new Url('entity.user.edit_form', ['user' => $user->id()], $options))->toString();
    $url = new Url('entity.user.edit_form', ['user' => $user->id(), $options]);
    $welcom_back_text = $this->t('Welcome back');

    $markup_data = '<div class="content"> <div class="welcome"><p class="welcome-back">' . $welcom_back_text . '</p><p class="name"> ' . $name . ' </p></div><div class="action-links"><a class="button button-action button--primary button--small" href="' . $url->toString() . '">' . $this->t('Edit Account') . '</a></div></div>';

    return [
      '#type' => 'markup',
      '#markup' => $markup_data,
    ];
  }

}
