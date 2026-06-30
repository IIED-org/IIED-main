<?php

namespace Drupal\layout_paragraphs_custom_host_entity_test\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Render\RendererInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the lp host entity entity edit forms.
 */
class LpHostEntityForm extends ContentEntityForm {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Renderer\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, RendererInterface $renderer) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => $this->renderer->render($link)];

    if ($result == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New lp host entity %label has been created.', $message_arguments));
      $this->logger('layout_paragraphs_custom_host_entity_test')->notice('Created new lp host entity %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The lp host entity %label has been updated.', $message_arguments));
      $this->logger('layout_paragraphs_custom_host_entity_test')->notice('Updated new lp host entity %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.lp_host_entity.canonical', ['lp_host_entity' => $entity->id()]);
    return $result;
  }

}
