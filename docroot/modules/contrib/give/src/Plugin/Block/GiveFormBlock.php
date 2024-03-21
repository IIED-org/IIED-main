<?php

namespace Drupal\give\Plugin\Block;

use Drupal\give\Entity\Donation;
use Drupal\give\Entity\GiveForm;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Utility\Html;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Display a give form
 *
 * @Block(
 *   id = "give_form",
 *   admin_label = @Translation("Give form"),
 *   category = @Translation("Community Accounting")
 * )
 * @todo the block should not be cached longer that the stripe token is valid
 */
class GiveFormBlock extends BlockBase implements ContainerFactoryPluginInterface{

  protected $entityFormBuilder;

  /**
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   * @param EntityFormBuilderInterface $entity_form_builder
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityFormBuilderInterface $entity_form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFormBuilder = $entity_form_builder;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $forms = GiveForm::loadMultiple();
    return [
      'give_form_id' => key($forms)
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) : AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'access give forms')->cachePerUser();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    parent::blockForm($form, $form_state);
    $forms = GiveForm::loadMultiple();
    $contextRepo = \Drupal::service('entity.repository');
    foreach ($forms as $give_form) {
      $label = $contextRepo->getTranslationFromContext($give_form)->label();
      $list[$give_form->id()] = Html::escape($label);
    }
    $form['give_form_id'] = [
      '#title' => $this->t('Show form'),
      '#type' => 'radios',
      '#options' => $list,
      '#default_value' => $this->configuration['give_form_id'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $donation = Donation::create([
     'give_form' => $this->configuration['give_form_id'],
    ]);
    $output = $this->entityFormBuilder->getForm($donation);
    $output['#title'] = $donation->getGiveForm()->label();
    return $output;
  }


  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
