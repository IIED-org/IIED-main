<?php

namespace Drupal\give\Entity;

use Drupal\Core\Field\PluginSettingsInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\give\ProblemLog;

/**
 * Render controller for give donations.
 * @todo more work needed. Either EntityViewDisplay should be used or a new
 * theme callback and template
 */
class DonationViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    static $w = 0;
    $build = parent::view($entity, $view_mode, $langcode);
    $this->bundle = $entity->give_form->target_id;
    $this->viewMode = $view_mode;
    $this->entity = $entity;

    $build['mail'] = $this->getFieldFormatter('mail')
      ->view($entity->mail, ['weight' => $w++]);

    $build['amount'] = $this->getFieldFormatter('amount', 'give_cents_to_dollars')
      ->view($entity->amount, ['weight' => $w++]);

    $build['uid'] = $this->getFieldFormatter('uid', 'entity_reference_label')
      ->view($entity->uid);
    if ($ad = $donation->showAddress()) {
      $build['address'] = [
        // Todo need to find a way to display the field title.
        '#markup' => Markup::create($ad),
        '#weight' => $w++
      ];
    }

    $build['give_form'] = $this->getFieldFormatter('give_form', 'entity_reference_label')
      ->view($entity->give_form, ['weight' => $w++]);

    $build['method'] = $this->getFieldFormatter('method')
      ->view($entity->method, ['weight' => $w++]);
    //payment_method_names()[$entity->getGiveForm()->id()]


    $result = ProblemLog::load($entity->uuid());

    $build['errors'] = [
      '#type' => 'fieldset',
      '#title' => 'Problem log',
      '#weight' => 20,
      'table' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Problem type'),
          $this->t('Detail'),
          $this->t("Browser's user agent"),
          $this->t('Time'),
        ],
        '#empty' => $this->t('No problems recorded.'),
        '#weight' => $w++,
        '#access' => $result && \Drupal::currentUser()->hasPermission('manage donations')
      ]
    ];

    foreach ($result as $row) {
      $build['errors']['table']['#rows'][] = [
        $row->type,
        $row->detail,
        $row->user_agent,
        \Drupal::service('date.formatter')->format($row->timestamp, 'short'),
      ];
    }

    return $build;
  }

  private function getFieldFormatter(string $field_name, $formatter_id = 'basic_string') : PluginSettingsInterface {

    $definitions = \Drupal::service('entity_field.manager')
      ->getFieldDefinitions('give_donation', $this->bundle);

    $formatter = \Drupal::service('plugin.manager.field.formatter')->getInstance([
      'field_definition' => $definitions[$field_name],
      'view_mode' => $this->viewMode,
      // No need to prepare, defaults have been merged in setComponent().
      'prepare' => FALSE,
      'configuration' => [
        'type' => $formatter_id,// the formatter id
        'label' => 'above',
        'settings' => [],
        'third_party_settings' => []
      ],
    ]);
    $formatter->prepareView([$this->entity->id() => $this->entity->{$field_name}]);
    return $formatter;
  }

}
