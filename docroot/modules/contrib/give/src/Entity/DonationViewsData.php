<?php

namespace Drupal\give\Entity;

use Drupal\views\EntityViewsData;

/**
 * Views Data for Transaction entity.
 */
class DonationViewsData extends EntityViewsData {

  /**
   *  {@inheritdoc}
   */
  function getViewsData() {
    $data = parent::getViewsData();
    // This plugin, included in the give module, allows grouping all donations by year.
    // See the default view 'summary' display.
    $data['give_donation']['created']['field']['id'] = 'views_date_format_sql_field';
    
    $data['give_donation']['amount'] = [
      'title' => $this->t('Amount'),
      'help' => $this->t('The formatted value of the donation'),
      'field' => [
        'id' => 'give_amount',
      ],
    ];
    // Uses the owner entity reference but falls back to the suppied name if
    // donor wasn't a member.
    $data['give_donation']['name']['field']['id'] = 'give_name';

    $data['give_donation']['method']['filter'] = [
      'id' => 'in_operator',
      'options callback' => 'payment_method_names'
    ];
    $data['give_donation']['recurring']['field']['id'] = 'give_recurrence';

    return $data;
  }
}

