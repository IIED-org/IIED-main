<?php

namespace Drupal\views_templates;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the view template entity add forms.
 */
class ViewTemplateForm extends FormBase {

  /**
   * The plugin manager interface.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $builderManager;

  /**
   * Constructs a new ViewsBuilderController object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $builderManager
   *   The Views Builder Plugin Interface.
   */
  public function __construct(PluginManagerInterface $builderManager) {
    $this->builderManager = $builderManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.views_templates.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $builder = $this->createBuilder($form_state->getValue('builder_id'));
    $values = $form_state->cleanValues()->getValues();
    $view = $builder->createView($values);
    $view->save();

    // Redirect the user to the view admin form.
    $form_state->setRedirectUrl($view->toUrl('edit-form'));
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $view_template = NULL) {
    $builder = $this->createBuilder($view_template);
    $form['#title'] = $this->t('Duplicate of @label', ['@label' => $builder->getAdminLabel()]);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('View name'),
      '#required' => TRUE,
      '#size' => 32,
      '#maxlength' => 255,
      '#default_value' => $builder->getAdminLabel(),
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#maxlength' => 128,
      '#machine_name' => [
        'exists' => '\Drupal\views\Views::getView',
        'source' => ['label'],
      ],
      '#default_value' => '',
      '#description' => $this->t('A unique machine-readable name for this View. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $builder->getDescription(),
    ];
    $form['builder_id'] = [
      '#type' => 'value',
      '#value' => $builder->getPluginId(),
    ];

    $form += $builder->buildConfigurationForm($form, $form_state);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create View'),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'views_templates_add';
  }

  /**
   * Function to create builder.
   *
   * @param mixed $plugin_id
   *   The plugin it to create builder.
   *
   * @return \Drupal\views_templates\Plugin\ViewsBuilderPluginInterface
   *   Returns a builder.
   */
  public function createBuilder($plugin_id) {
    return $this->builderManager->createInstance($plugin_id);
  }

}
