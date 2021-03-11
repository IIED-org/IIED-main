<?php

namespace Drupal\varbase_total_control\Plugin\Block;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Create content'.
 *
 * @Block(
 * id = "varbase_create_content",
 * admin_label = @Translation("Create New Content"),
 * category = @Translation("Dashboard")
 * )
 */
class VarbaseCreateContent extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

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
   * The renderer service.
   *
   * @var Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

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
   * Creates a VarbaseCreateContent block instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   AccountProxy current user definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TranslationInterface $string_translation, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, RedirectDestinationInterface $redirect_destination, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->stringTranslation = $string_translation;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->redirectDestination = $redirect_destination;
    $this->currentUser = $current_user;
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
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('redirect.destination'),
      $container->get('current_user'),
      $container->get('entity.manager')->getStorage('user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $types = array_reverse($this->entityTypeManager->getStorage('node_type')->loadMultiple());
    $links = [];
    $config = $this->getConfiguration();
    $destination = $this->redirectDestination->getAsArray();
    $options = [
      $destination,
    ];

    foreach ($types as $type => $object) {
      // Check against pane config for type.
      if ((!array_key_exists($type, $config['total_control_admin_types_links'])) || (isset($config['total_control_admin_types_links']) && $config['total_control_admin_types_links'][$type]) == $type) {
        // Check access, then add a link to create content.
        if ($this->currentUser->hasPermission('create ' . $object->get('type') . ' content')) {
          $link_options = [
            'attributes' => [
              'class' => [
                Html::cleanCssIdentifier(mb_strtolower($object->get('type'))),
              ],
            ],
          ];
          $url = new Url('node.add', ['node_type' => $object->get('type')], $options);
          $url->setOptions($link_options);
          $links[] = Link::fromTextAndUrl($object->get('name'),
           $url);
        }
      }
    }
    $links[] = Link::fromTextAndUrl($this->t('More...'),
      new Url('node.add_page', $options));

    $body_data = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $links,
    ];

    $markup_data = $this->renderer->render($body_data);

    return [
      '#type' => 'markup',
      '#markup' => $markup_data,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();
    $types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $type_defaults = [];

    foreach ($types as $type => $object) {
      if (!array_key_exists($type, $type_defaults)) {
        $type_defaults[$type] = $type;
      }
    }

    $form['total_control_admin_types_links'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Include Create links for Content Types'),
      '#options' => $type_defaults,
      '#default_value' => $config['total_control_admin_types_links'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['total_control_admin_types_links'] = $values['total_control_admin_types_links'];
  }

}
