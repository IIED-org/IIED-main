<?php

namespace Drupal\give\Entity;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of give donation entities.
 */
class DonationListBuilder extends EntityListBuilder {

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   */
  public function __construct($entity_type, $storage, DateFormatterInterface $date_formatter) {
    $this->entityTypeId = $entity_type->id();
    $this->storage = $storage;
    $this->entityType = $entity_type;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      ->sort($this->entityType->getKey('id'), 'DESC');
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    // The weight is ignored (at least by views), so put the complete op first.
    $ops = parent::getOperations($entity);
    if (!$entity->complete->value) {
      $ops = [
        'complete' => [
          'title' => $this->t('Complete'),
          'weight' => 0,
          'url' => $this->ensureDestination($entity->toUrl('complete-form'))
        ]
      ]+ $ops;
    }
    return $ops;
  }


  public function buildHeader() {
    return [
      'id' => $this->t('ID'),
      'date' => $this->t('Date'),
      'donor' => $this->t('Name'),
      'amount' => $this->t('Amount'),
      'form' => $this->t('Form'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $amount = $entity->getFormattedAmount();
    if ($entity->isCompleted()) {
      $amount = Markup::create($amount .' &#10004;');
    }
    $form = $entity->getGiveForm();
    return [
      'id' => Link::fromTextAndUrl(
        '#'.$entity->id(),
        Url::fromRoute('entity.give_donation.canonical', ['give_donation' => $entity->id()])
      ),
      'date' => $this->dateFormatter->format($entity->getCreatedTime()),
      'donor' => Markup::create($entity->getDonorName(TRUE)),
      'amount' => $amount,
      'form' => $form ? $form->label() : $entity->give_form->target_id . '('.$this->t('removed').')',
    ] + parent::buildRow($entity);
  }

}
