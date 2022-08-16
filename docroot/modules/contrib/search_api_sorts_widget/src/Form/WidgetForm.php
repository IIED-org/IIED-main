<?php

namespace Drupal\search_api_sorts_widget\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\search_api_sorts\ConfigIdEscapeTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Search API Sorts Widget form.
 */
class WidgetForm extends FormBase {
  use ConfigIdEscapeTrait;

  /**
   * The search api sorts widget storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $searchApiSortsWidgetStorage;

  /**
   * Constructs the DisplaySortsForm object.
   *
   * @param \Drupal\search_api\Display\DisplayPluginManagerInterface $display_plugin_manager
   *   The search_api display plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->searchApiSortsWidgetStorage = $entity_type_manager
      ->getStorage('search_api_sorts_widget');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_api_sorts_widget_widget';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    $content = NULL,
    $derivative_plugin_id = NULL
  ) {
    if (!$content || !$derivative_plugin_id) {
      return $content;
    }

    $config_id = $this->getEscapedConfigId($derivative_plugin_id);
    $settings = $this->searchApiSortsWidgetStorage->load($config_id);
    if (empty($settings) || !$settings->get('status')) {
      return $content;
    }
    $links = $content['links'];

    $new_items = [];
    $default = '';
    $sort_fields = array_column($links['#items'], '#sort_field');
    foreach ($settings->get('sorts') as $name => $setting) {
      $key = array_search($name, $sort_fields);
      if (!empty($links['#items'][$key])) {
        $link = $links['#items'][$key];
        if (!empty($setting['label_asc'])) {
          $new_items[$name . '|asc'] = $setting['label_asc'];
        }
        if (!empty($setting['label_desc'])) {
          $new_items[$name . '|desc'] = $setting['label_desc'];
        }
        if ($link['#active']) {
          $default = $name . '|' . ($link['#order'] == 'asc' ? 'desc' : 'asc');
        }
      }
    }
    $form_state->set('links', $links);

    $form['sort_by'] = array(
      '#type' => 'select',
      '#options' => $new_items,
      '#default_value' => $default,
    );
    if ($settings->get('autosubmit')) {
      $form['sort_by']['#attributes']['onChange'] = 'this.form.submit();';
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sort'),
    ];
    if ($settings->get('autosubmit_hide')) {
      $form['actions']['submit']['#attributes']['style'] = ['display: none;'];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $links = $form_state->get('links');
    [$key, $order] = explode('|', $form_state->getValue('sort_by'));

    foreach ($links['#items'] as $link) {
      $name = $link['#sort_field'];
      if ($name == $key) {
        $url = $link['#url'];

        $url_info = parse_url($url);
        parse_str($url_info['query'], $query);
        $query['order'] = $order;
        $url_info['query'] = UrlHelper::buildQuery($query);
        $url = $url_info['path'] . '?' . $url_info['query'];

        $form_state->setRedirectUrl(Url::fromUserInput($url));
      }
    }
  }

}
