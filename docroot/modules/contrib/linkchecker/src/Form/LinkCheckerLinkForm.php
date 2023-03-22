<?php

namespace Drupal\linkchecker\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the linkchecker link edit forms.
 */
class LinkCheckerLinkForm extends ContentEntityForm {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * LinkCheckerLinkEditForm constructor.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, DateFormatterInterface $dateFormatter) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\linkchecker\Entity\LinkCheckerLink $link */
    $link = $this->entity;

    // Ensure the url passed to t() is not NULL.
    $url = $link->getUrl() ?? '';
    if ($link->getLastCheckTime() !== NULL) {
      $description = $this->t('The link <a href=":url">:url</a> was last checked on @last_checked and failed @fail_count times.',
        [
          ':url' => $url,
          '@fail_count' => $link->getFailCount(),
          '@last_checked' => $link->getLastCheckTime() !== NULL ? $this->dateFormatter->format($link->getLastCheckTime()) : $this->t('Never'),
        ]);
    }
    else {
      $description = $this->t('The link <a href=":url">:url</a> was never checked before.', [
        ':url' => $url,
      ]);
    }

    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Settings'),
      '#description' => $description,
      '#open' => TRUE,
    ];

    $form['settings']['method'] = [
      '#type' => 'select',
      '#title' => $this->t('Select request method'),
      '#default_value' => $link->getRequestMethod(),
      '#options' => [
        'HEAD' => $this->t('HEAD'),
        'GET' => $this->t('GET'),
      ],
      '#description' => $this->t('Select the request method used for link checks of this link. If you encounter issues like status code 500 errors with the HEAD request method you should try the GET request method before ignoring a link.'),
    ];

    $form['settings']['status'] = [
      '#default_value' => $link->isLinkCheckStatus(),
      '#type' => 'checkbox',
      '#title' => $this->t('Check link status'),
      '#description' => $this->t('Uncheck if you wish to ignore this link. Use this setting only as a last resort if there is no other way to solve a failed link check.'),
    ];

    return $form;
  }

}
