<?php

namespace Drupal\media_pdf_thumbnail\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PdfImageEntityPurgeForm.
 *
 * Purge PDF image entity.
 *
 * @package Drupal\media_pdf_thumbnail\Form
 */
class PdfImageEntityPurgeForm extends FormBase {

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
   * ConfigFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): PdfImageEntityPurgeForm | static {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->messenger = $container->get('messenger');
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * Purge callback.
   *
   * @param string|int $id
   *   ID.
   * @param array $context
   *   Context.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException|\Drupal\Core\Entity\EntityStorageException
   */
  public static function execute(string | int $id, array &$context): void {
    $entity = \Drupal::entityTypeManager()
      ->getStorage('pdf_image_entity')
      ->load($id);
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
    return 'pdf_image_entity_purge_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
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
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $operations = [];

    $ids = $this->entityTypeManager
      ->getStorage('pdf_image_entity')
      ->getQuery()
      ->accessCheck(FALSE)
      ->execute();

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
