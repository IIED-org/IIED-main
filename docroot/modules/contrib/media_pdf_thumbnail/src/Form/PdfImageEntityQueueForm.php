<?php

namespace Drupal\media_pdf_thumbnail\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\media_pdf_thumbnail\Manager\PdfImageEntityQueueManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PdfImageEntityQueueForm
 *
 * @package Drupal\media_pdf_thumbnail\Form
 */
class PdfImageEntityQueueForm extends FormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * @var \Drupal\media_pdf_thumbnail\Manager\PdfImageEntityQueueManager
   */
  protected PdfImageEntityQueueManager $pdfImageEntityQueueManager;

  /**
   * PdfImageEntityQueueForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   * @param \Drupal\media_pdf_thumbnail\Manager\PdfImageEntityQueueManager $pdfImageEntityQueueManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, MessengerInterface $messenger, PdfImageEntityQueueManager $pdfImageEntityQueueManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->messenger = $messenger;
    $this->pdfImageEntityQueueManager = $pdfImageEntityQueueManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'), $container->get('messenger'), $container->get('media_pdf_thumbnail.pdf_image_entity.queue.manager'));
  }

  /**
   * @param $data
   * @param $context
   *
   * @return void
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function execute($data, &$context) {
    $context['message'] = 'Processing - ' . $data->entity->label();
    $context['results'][] = $data->id;
    \Drupal::service('media_pdf_thumbnail.image.manager')->createThumbnail($data->entity, $data->fieldName, $data->imageFormat, $data->page);
    Cache::invalidateTags($data->entity->getCacheTagsToInvalidate());
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
    return 'pdf_image_entity_queue_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['total'] = [
      '#type' => 'markup',
      '#markup' => '<div>' . t('Number of items in queue') . ' : <strong>' . $this->pdfImageEntityQueueManager->getNumberOfItems() . '</strong></div>',
    ];

    $form['actions'] = [
      'run' => [
        '#type' => 'submit',
        '#value' => $this->t('Run'),
        '#description' => $this->t('Runs queue now instead of waiting for cron.'),
        '#submit' => ['::run'],
      ],
      'clear' => [
        '#type' => 'submit',
        '#value' => $this->t('Clear'),
        '#description' => $this->t('Clear items in queue.'),
        '#submit' => ['::clear'],
      ],
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
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return void
   */
  public function run(&$form, FormStateInterface $form_state) {

    $generateQueue = $this->pdfImageEntityQueueManager->getQueue();

    $operations = [];

    while ($item = $generateQueue->claimItem()) {
      try {
        $operations[] = [
          'Drupal\media_pdf_thumbnail\Form\PdfImageEntityQueueForm::execute',
          [$item->data],
        ];
        $generateQueue->deleteItem($item);
      }
      catch (\Exception $e) {
        $this->messenger->addMessage($e->getMessage());
      }
    }
    $this->pdfImageEntityQueueManager->clearQueue();

    $batch = [
      'title' => t('Generate PDF images'),
      'operations' => $operations,
      'init_message' => t('Task creating process is starting.'),
      'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
      'error_message' => t('An error occurred during processing'),
      'finished' => '\Drupal\media_pdf_thumbnail\Form\PdfImageEntityQueueForm::finishedCallback',
    ];

    batch_set($batch);
  }

  /**
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return void
   */
  public function clear(&$form, FormStateInterface $form_state) {
    $this->pdfImageEntityQueueManager->clearQueue();
    $this->messenger->addStatus(t('Queue cleared'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
