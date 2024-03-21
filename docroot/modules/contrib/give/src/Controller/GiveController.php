<?php

namespace Drupal\give\Controller;
use Drupal\give\Entity\Donation;
use Drupal\give\Entity\GiveForm;
use Drupal\Core\Controller\ControllerBase;
use Drupal\give\GiveFormInterface;
use Drupal\give\DonationInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller routines for give routes.
 */
class GiveController extends ControllerBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a GiveController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Presents the give form.
   *
   * @param \Drupal\give\GiveFormInterface $give_form
   *   The give form to use.
   *
   * @return array
   *   The form as render array as expected by drupal_render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Exception is thrown when user tries to access non existing default
   *   give form.
   */
  public function giveSitePage(GiveFormInterface $give_form = NULL) {
    $config = $this->config('give.settings');

    // Use the default form if no form has been passed.
    if (empty($give_form)) {
      $give_form = GiveForm::load($config->get('default_form'));
      // If there are no forms, do not display the form.
      if (empty($give_form)) {
        if ($this->currentUser()->hasPermission('administer give')) {
          \Drupal::messenger()->addStatus($this->t('The give form has not been configured. <a href=":add">Add one or more forms</a> .', [
            ':add' => $this->url('give.form_add'),
          ]), 'error');
          return [];
        }
        else {
          throw new NotFoundHttpException();
        }
      }
    }
    $donation = Donation::create([
      'give_form' => $give_form->id(),
    ]);
    $form = $this->entityFormBuilder()->getForm($donation);
    $form['#title'] = $give_form->label();
    $form['#cache']['contexts'][] = 'user.permissions';
    $this->renderer->addCacheableDependency($form, $config);
    return $form;
  }

  /**
   * Presents the second page of the give form which takes donations.
   *
   * @param \Drupal\give\GiveFormInterface $give_form
   *   The give form to use.
   * @param \Drupal\give\DonationInterface $give_donation
   *   The donation for which payment is to be processed.
   *
   * @return array
   *   The form as render array as expected by drupal_render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Exception is thrown when user tries to access non existing donation or
   *   give form.
   */
  public function takeDonation(GiveFormInterface $give_form, DonationInterface $give_donation) {
    $config = $this->config('give.settings');

    $form = $this->entityFormBuilder()->getForm($give_donation, 'add');
    $form['#title'] = $give_form->label();
    $form['#cache']['contexts'][] = 'user.permissions';
    $this->renderer->addCacheableDependency($form, $config);
    return $form;
  }

  /**
   * Presents a preview of the acknowledgement e-mail.
   *
   * @param \Drupal\give\GiveFormInterface $give_form
   *   The give form to use.
   *
   * @return array
   *   The preview as render array as expected by drupal_render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Exception is thrown when user tries to access non existing give form.
   */
  public function givePreviewReply(GiveFormInterface $give_form) {
    $render = [];

    $mail_handler = \Drupal::service('give.mail_handler');
    $previews = $mail_handler->makeDonationReceiptPreviews($give_form, $this->entityTypeManager());

    if ($give_form->get('autoreply')) {
      $render['autoreply_status']['#markup'] = '<p>' . $this->t('<strong>Autoreply status: On.</strong> Receipts are enabled.') . '</h2>';
    }
    else {
      $render['autoreply_status']['#markup'] = '<p>' . $this->t('<strong>Autoreply status: Off.</strong> This donation form will <strong>not</strong> send automatic acknowledgement replies.  <a href="@url">Edit it to enable autoreplies</a>.', ['@url' => $give_form->toUrl('edit-form')->toString()]) . '</p>';
    }

    $titles = [
      'receipt_card' => $this->t('Preview of One-time donation reply e-mail for @label forms', ['@label' => $give_form->label()]),
      'receipt_card_recurring' => $this->t('Preview of Recurring donation reply e-mail for @label forms', ['@label' => $give_form->label()]),
      'receipt_check' => $this->t('Preview of Check (pledged) donation reply e-mail for @label forms', ['@label' => $give_form->label()]),
      'admin_notice' => $this->t('Preview of (admin) recipients\' notification e-mail for @label forms', ['@label' => $give_form->label()]),
    ];

    foreach ($previews as $key => $email) {
      $rndr = [];
      $rndr['title']['#markup'] = '<h2>' . $titles[$key] . '</h2>';
      $rndr['subject']['#markup'] = '<p>' . $this->t('<strong>Subject:</strong> @subject', ['@subject' => $email['subject']]) . '</p>';
      $rndr['body']['#markup'] = $email['body'];
      $rndr['separator']['#markup'] = '<hr />';
      $render[$key] = $rndr;
    }

    return $render;
  }

}
