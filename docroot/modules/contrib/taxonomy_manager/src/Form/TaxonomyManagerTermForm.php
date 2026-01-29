<?php

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Render\Element;
use Drupal\taxonomy\TermForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Taxonomy manager class.
 */
class TaxonomyManagerTermForm extends TermForm {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, LanguageManager $language_manager, EntityFieldManager $entity_field_manager) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->languageManager = $language_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('language_manager'),
      $container->get('entity_field.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\taxonomy\Entity\Term $term */
    $term = $this->getEntity();
    $form['#parents'] = [];
    $form['vid'] = [
      '#type' => 'value',
      '#value' => $term->bundle(),
    ];

    $form['tid'] = [
      '#type' => 'value',
      '#value' => $term->id(),
    ];

    $original_form = parent::form($form, $form_state);
    $original_fields = array_intersect_key($original_form, array_flip(Element::children($original_form)));

    $original_fields['langcode']['#access'] = FALSE;

    $form['fieldset']['#type'] = 'fieldset';
    $form['fieldset']['#title'] = $this->fieldsetTitle($term->getName(), $term->id());
    $form['fieldset']['original'] = $original_fields;

    if (taxonomy_manager_vocabulary_translatable($term->bundle())) {
      $form['translatable'] = [
        '#type' => 'details',
        '#title' => $this->t("Translatable fields"),
        '#open' => TRUE,
      ];

      $display = $form_state->get('form_display');

      foreach ($this->translatableFieldInfo() as $langcode => $fields) {
        $form['translatable'][$langcode] = [
          '#type' => 'details',
          '#title' => $this->languageManager->getLanguageName($langcode),
          '#open' => FALSE,
        ];

        if ($term->hasTranslation($langcode)) {
          /** @var \Drupal\taxonomy\Entity\Term $entity */
          $entity = $term->getTranslation($langcode);
        }
        else {
          /** @var \Drupal\taxonomy\Entity\Term $entity */
          $entity = $this->entityTypeManager->getStorage($term->getEntityTypeId())
            ->create([
              'vid' => $term->bundle(),
            ]);
        }

        foreach ($fields as $source_field => $translated_field) {
          if (isset($original_fields[$source_field])) {
            $element = [
              '#title' => $this->entity->getFieldDefinition($source_field)->getLabel(),
              '#required' => $this->entity->getFieldDefinition($source_field)->isRequired(),
              '#access' => $original_fields[$source_field]['#access'],
            ];
            if ($source_field !== 'description') {
              $element['#attributes']['name'] = $translated_field;
            }
            // It is necessary to provide default value to avoid element
            // widget errors. It is a term default value, that is easy to
            // change.
            $value = $entity->get($source_field)->isEmpty() ? $term->get($source_field) : $entity->get($source_field);
            $form['translatable'][$langcode][$translated_field] = $display->getRenderer($source_field)
              ->formElement(
                $value,
                0,
                $element,
                $form,
                $form_state
              );
          }
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function saveTranslations(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $term = $this->entity;
    foreach ($this->translatableFieldInfo() as $langcode => $fields) {
      $data = [];
      foreach ($fields as $source_field => $translated_field) {
        if (isset($input[$translated_field])) {
          $data[$source_field] = $input[$translated_field];
        }
      }

      if ($term->hasTranslation($langcode)) {
        $translation = $term->getTranslation($langcode);
        foreach ($data as $field => $value) {
          $translation->set($field, $value);
        }
        $translation->save();
      }
      else {
        $term->addTranslation($langcode, $data);
        $term->save();
        $this->messenger->addStatus($this->t('A new translation in %lang language has been created.', ['%lang' => $this->languageManager->getLanguageName($langcode)]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state): array {
    $element['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save term'),
      '#weight' => 20,
      '#submit' => ['::submitForm', '::save'],
    ];

    if (taxonomy_manager_vocabulary_translatable($this->entity->bundle())) {
      $element['submit']['#submit'][] = '::saveTranslations';
    }

    return $element;
  }

  /**
   * Generates a title for a fieldset with link to the term page.
   *
   * @param string $title
   *   The fieldset title text (usually the term name).
   * @param string $id
   *   The taxonomy term ID to be included in parentheses and used in the URL.
   *
   * @return string
   *   HTML markup for the fieldset title, including a link to the term page.
   *   The title will be formatted as "Title (ID)" with a link to
   *   "/taxonomy/term/{id}".
   */
  protected function fieldsetTitle($title, $id) {
    $display_title = $title . ' (' . $id . ')';
    // Link has its own XSS prevention mecanism, no Html::escape is required.
    return Link::fromTextAndUrl($display_title, Url::fromUri('internal:/taxonomy/term/' . $id, [
      'attributes' => ['title' => 'View term page'],
    ]))->toString();
  }

  /**
   * Get mapping for translatable fields.
   *
   * @return array
   *   The translatable field information.
   */
  protected function translatableFieldInfo() {
    $info = [];

    $definitions = $this->entityFieldManager->getFieldDefinitions($this->entity->getEntityTypeId(), $this->entity->bundle());

    $langcode = $this->entity->language()->getId();
    $languages = $this->languageManager->getLanguages();
    unset($languages[$langcode]);

    foreach ($languages as $langcode => $language) {
      foreach ($definitions as $field_name => $field_definition) {
        if ($field_definition->isTranslatable()) {
          $info[$langcode][$field_name] = implode("_", [
            'taxonomy_manager',
            $field_name,
            $langcode,
          ]);
        }
      }
    }

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Save the term entity.
    $result = parent::save($form, $form_state);

    // Redirect to the taxonomy manager vocabulary page.
    $form_state->setRedirect(
      'taxonomy_manager.admin_vocabulary',
      [
        'taxonomy_vocabulary' => $this->entity->bundle(),
      ]
    );
    return $result;
  }

}
