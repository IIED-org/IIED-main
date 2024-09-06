<?php

namespace Drupal\media_pdf_thumbnail\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PdfImageEntityPurgeForm
 *
 * @package Drupal\media_pdf_thumbnail\Form
 */
class PdfImageEntityPurgeForm extends FormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * PdfImageEntityPurgeForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Messenger\Messenger $messenger
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, MessengerInterface $messenger, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->messenger = $messenger;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'), $container->get('messenger'), $container->get('config.factory'));
  }

  /**
   * @param $id
   * @param $context
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function execute($id, &$context) {
    $entity = \Drupal::entityTypeManager()->getStorage('pdf_image_entity')->load($id);
    $context['message'] = 'Processing - ' . $entity->id();
    $context['results'][] = $entity->id();
    $entity->delete();
  }

  /**
   * Finish callback.
   *
   * @param mixed $success
   *   Success.
   * @param mixed $results
   *   Results.
   * @param mixed $operations
   *   Operations.
   */
  public static function finishedCallback($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(count($results),
        'One task processed.',
        '@count tasks processed.');
      \Drupal::messenger()->addMessage($message);
    }
    else {
      \Drupal::messenger()->addError(t('Finished with an error.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pdf_image_entity_purge_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clean'),
      '#description' => $this->t('Submitting this form will delete all PDF entities and image files.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $operations = [];

    $ids = \Drupal::entityTypeManager()->getStorage('pdf_image_entity')->getQuery()->accessCheck(false)->execute();

    foreach ($ids as $id) {
      $operations[] = [
        'Drupal\media_pdf_thumbnail\Form\PdfImageEntityPurgeForm::execute',
        [$id],
      ];
    }

    $batch = [
      'title' => t('Delete PDF image'),
      'operations' => $operations,
      'init_message' => t('Task creating process is starting.'),
      'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
      'error_message' => t('An error occurred during processing'),
      'finished' => '\Drupal\media_pdf_thumbnail\Form\PdfImageEntityPurgeForm::finishedCallback',
    ];

    batch_set($batch);
  }

}
