<?php

namespace Drupal\media_pdf_thumbnail\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_pdf_thumbnail\Manager\PdfImageEntityPurgeManager;
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
   * @var \Drupal\media_pdf_thumbnail\Manager\PdfImageEntityPurgeManager
   */
  protected PdfImageEntityPurgeManager $pdfImageEntityPurgeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): PdfImageEntityPurgeForm | static {
    $instance = parent::create($container);
    $instance->pdfImageEntityPurgeManager = $container->get('media_pdf_thumbnail.pdf_image_entity.purge.manager');
    return $instance;
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
    try {
      $this->pdfImageEntityPurgeManager->purgePdfImageEntities();
    }
    catch (\Exception $e) {
      $this->messenger()->addError($e->getMessage());
    }
  }

}
