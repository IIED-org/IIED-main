<?php

namespace Drupal\taxonomy_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Controller routines for taxonomy_manager routes.
 */
class MainController extends ControllerBase {

  /**
   * List of vocabularies, which link to Taxonomy Manager interface.
   *
   * @return array
   *   A render array representing the page.
   */
  public function listVocabularies() {
    $build = [];

    if ($this->currentUser()->hasPermission('administer taxonomy')) {
      $build[] = [
        '#type' => 'link',
        '#title' => $this->t('Add new vocabulary'),
        '#url' => Url::fromRoute('entity.taxonomy_vocabulary.add_form'),
        '#attributes' => [
          'class' => ['button', 'button--primary'],
        ],
      ];
    }

    if ($this->currentUser()->hasPermission('access taxonomy overview')) {
      $build[] = [
        '#type' => 'link',
        '#title' => $this->t('Edit vocabulary settings'),
        '#url' => Url::fromRoute('entity.taxonomy_vocabulary.collection'),
        '#attributes' => [
          'class' => ['button', 'button--secondary'],
        ],
      ];
    }

    $voc_list = [];
    $vocabularies = $this->entityTypeManager()->getStorage('taxonomy_vocabulary')->loadMultiple();
    foreach ($vocabularies as $vocabulary) {
      if ($this->entityTypeManager()->getAccessControlHandler('taxonomy_term')->createAccess($vocabulary->id())) {
        $vocabulary_form = Url::fromRoute('taxonomy_manager.admin_vocabulary',
          ['taxonomy_vocabulary' => $vocabulary->id()]);
        $voc_list[] = ['data' => [Link::fromTextAndUrl($vocabulary->label(), $vocabulary_form)]];
      }
    }

    if (!count($voc_list)) {
      $voc_list[] = ['#markup' => $this->t('No Vocabularies available')];
    }

    $header = ['Vocabularies'];

    $build['vocabularies'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $voc_list,
    ];

    return $build;
  }

}
