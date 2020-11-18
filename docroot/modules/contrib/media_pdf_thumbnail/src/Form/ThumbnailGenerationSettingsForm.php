<?php

namespace Drupal\media_pdf_thumbnail\Form;

use Drupal;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ThumbnailGenerationSettingsForm
 *
 * @package Drupal\media_pdf_thumbnail\Form
 */
class ThumbnailGenerationSettingsForm extends FormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
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
    return new static($container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('config.factory'));
  }

  /**
   * RegenerateThumbnails.
   *
   * @param $mid
   *
   * @param $context
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function regenerateThumbnail($mid, &$context) {
    $media = Drupal::entityTypeManager()->getStorage('media')->load($mid);
    $media->save();
    $context['message'] = 'Processing - ' . $media->label();
    $context['results'][] = $media->label();
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
      $message = Drupal::translation()->formatPlural(count($results),
        'One task processed.',
        '@count tasks processed.');
      Drupal::messenger()->addMessage($message);
    }
    else {
      Drupal::messenger()->addError(t('Finished with an error.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'thumbnail_generation_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Regenerate thumbnails'),
      '#description' => $this->t('Regenerate thumbnail images for all media entities.'),
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
    $data = $this->configFactory->get('media_pdf_thumbnail.bundles.settings')
      ->getRawData();
    $bundles = [];

    foreach ($data as $name => $item) {
      $bundle = substr($name, 0, strpos($name, '_field'));
      if (!empty($data[$bundle . '_enable'])) {
        $bundles[] = $bundle;
      }
    }

    if (empty($bundles)) {
      return;
    }

    $mids = $this->entityTypeManager->getStorage('media')
      ->getQuery()
      ->condition('bundle', $bundles, 'IN')
      ->execute();

    $operations = [];

    foreach ($mids as $mid) {
      $operations[] = [
        'Drupal\media_pdf_thumbnail\Form\ThumbnailGenerationSettingsForm::regenerateThumbnail',
        [$mid],
      ];
    }

    $batch = [
      'title' => t('Regenerates media thumbnails'),
      'operations' => $operations,
      'init_message' => t('Thumbnail creating process is starting.'),
      'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
      'error_message' => t('An error occurred during processing'),
      'finished' => '\Drupal\media_pdf_thumbnail\Form\ThumbnailGenerationSettingsForm::finishedCallback',
    ];

    $batch['operations'] = batch_set($batch);
  }

}
