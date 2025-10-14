<?php

namespace Drupal\ds\Plugin\DsField\User;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\DsField\Field;

/**
 * Plugin that renders the username.
 */
#[DsField(
  id: 'usermail',
  title: new TranslatableMarkup('User e-mail'),
  entity_type: 'user',
  provider: 'user'
)]
class UserMail extends Field {

  /**
   * {@inheritdoc}
   */
  public function entityRenderKey() {
    return 'mail';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $settings['mail_link'] = [
      '#type' => 'checkbox',
      '#title' => 'Link to mail',
      '#default_value' => $config['mail_link'],
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary($settings) {
    $config = $this->getConfiguration();

    $summary = [];
    if (!empty($config['mail_link'])) {
      $summary[] = 'Link to mail: yes';
    }
    else {
      $summary[] = 'Link to mail: no';
    }

    return $summary;
  }
}
