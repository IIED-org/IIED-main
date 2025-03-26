<?php

namespace Drupal\media_pdf_thumbnail\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_pdf_thumbnail\Manager\PdfImageEntityQueueManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PdfImageEntityQueueForm.
 *
 * Form for PDF image queue.
 *
 * @package Drupal\media_pdf_thumbnail\Form
 */
class PdfImageEntityQueueForm extends FormBase {


  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Messenger.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * PdfImageEntityQueueManager.
   *
   * @var \Drupal\media_pdf_thumbnail\Manager\PdfImageEntityQueueManager
   */
  protected PdfImageEntityQueueManager $pdfImageEntityQueueManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): PdfImageEntityQueueForm | static {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->messenger = $container->get('messenger');
    $instance->pdfImageEntityQueueManager = $container->get('media_pdf_thumbnail.pdf_image_entity.queue.manager');
    return $instance;
  }

  /**
   * Form constructor.
   *
   * @param mixed $data
   *   Data.
   * @param mixed $context
   *   Context.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function execute(mixed $data, mixed &$context): void {
    $context['message'] = 'Processing - ' . $data->entity->label();
    $context['results'][] = $data->id;
    \Drupal::service('media_pdf_thumbnail.image.manager')
      ->createThumbnail($data->entity, $data->fieldName, $data->imageFormat, $data->page);
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
  public static function finishedCallback(mixed $success, mixed $results, mixed $operations): void {
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
  public function getFormId(): string {
    return 'pdf_image_entity_queue_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $text = $this->t('Number of items in queue');
    $form['total'] = [
      '#type' => 'markup',
      '#markup' => '<div>' . $text . ' : <strong>' . $this->pdfImageEntityQueueManager->getNumberOfItems() . '</strong></div>',
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
   * Run callback.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function run(array &$form, FormStateInterface $form_state): void {
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
      'title' => $this->t('Generate PDF images'),
      'operations' => $operations,
      'init_message' => $this->t('Task creating process is starting.'),
      'progress_message' => $this->t('Processed @current out of @total. Estimated time: @estimate.'),
      'error_message' => $this->t('An error occurred during processing'),
      'finished' => '\Drupal\media_pdf_thumbnail\Form\PdfImageEntityQueueForm::finishedCallback',
    ];

    batch_set($batch);
  }

  /**
   * Clear callback.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function clear(array &$form, FormStateInterface $form_state): void {
    $this->pdfImageEntityQueueManager->clearQueue();
    $this->messenger->addStatus($this->t('Queue cleared'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
