<?php

namespace Drupal\webform_content_creator\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform_content_creator\WebformContentCreatorUtilities;
use Drupal\Core\Entity\ContentEntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Form handler for the Webform content creator add and edit forms.
 */
class WebformContentCreatorForm extends EntityForm {

  /**
   * Entity type manager object.
   *
   * @var object
   */
  protected $entityTypeManager;

  /**
   * Entity type bundle info object.
   *
   * @var object
   */
  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityTypeBundleInfo = $container->get('entity_type.bundle.info');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->getTitle(),
      '#help' => $this->t('Configuration title'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
        'source' => ['title'],
      ],
      '#disabled' => !$this->entity->isNew(),
      '#description' => $this->t('A unique machine-readable name for this content type. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the %webform-content-creator-add page, in which underscores will be converted into hyphens.'),
    ];

    // Select with all webforms.
    $webforms_formatted = WebformContentCreatorUtilities::getFormattedWebforms();
    $form['webform'] = [
      '#type' => 'select',
      '#title' => $this->t('Webform'),
      '#options' => $webforms_formatted,
      '#default_value' => $this->entity->getWebform(),
      '#description' => $this->t("Webform title"),
      '#required' => TRUE,
    ];

    // Select with all entity types.
    $all_entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($all_entity_types as $entity_type_id => $entity_type_obj) {
      if ($entity_type_obj instanceof ContentEntityType) {
        $entity_types[$entity_type_id] = $entity_type_obj->getLabel(); 
      }
    }

    $form['target_entity_type'] = [
      '#type' => 'select',
      '#title' => t('Entity Type'),
      '#description' => t('Entity type'),
      '#required' => TRUE,
      '#options' => $entity_types,
      '#default_value' => $this->entity->getEntityTypeValue(),
      '#ajax' => [
        'callback' => [$this, 'getBundles'],  'event' => 'change',
        'method' => 'replace',
        'wrapper' => 'bundle-to-update',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Verifying...'),
        ],
      ],
    ];

    $options = [];
    $entity_id = $this->entity->getEntityTypeValue();
    if (empty($entity_id)) {
      $entity_id = array_key_first($entity_types);
    }
    $bundles_obj = $this->entityTypeBundleInfo->getBundleInfo($entity_id);
    foreach ($bundles_obj as $key => $value) {
        $options[$key] = $value['label'];
    }

    $form['target_bundle'] = [
      '#title' => t('Bundle'),
      '#type' => 'select',
      '#description' => t('Select the bundle'),
      '#required' => TRUE,
      '#options' => $options,
      '#default_value' => $this->entity->getBundleValue(),
      '#attributes' => ["id" => 'bundle-to-update'],
      '#validated' => TRUE
    ];

    $form['sync_content'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Synchronize Webform submission with the created content in edition'),
      '#description' => $this->t('Perform synchronization between webform submission and respective content when one is edited. When a webform submission is edited, the resultant content is synchronized with the new values.'),
      '#default_value' => $this->entity->getSyncEditContentCheck(),
    ];

    $form['sync_content_delete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Synchronize Webform submission with the created content in deletion'),
      '#description' => $this->t('Perform synchronization in deletion. When a webform submission is deleted, the resultant content is also deleted.'),
      '#default_value' => $this->entity->getSyncDeleteContentCheck(),
    ];

    $form['sync_content_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Synchronization field machine name'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->getSyncContentField(),
      '#help' => $this->t('When a webform submission is edited, the content which stores the webform submission id in this field is also updated. You have to create this field in the content type and then you have to map this field with Submission id. Example: field_submission_id'),
      '#states' => [
        'visible' =>
          [
            [
              ':input[name="sync_content"]' => ['checked' => TRUE],
            ],
            'or',
            [
              ':input[name="sync_content_delete"]' => ['checked' => TRUE],
            ],
          ],
        'required' =>
          [
            ':input[name="sync_content"]' => ['checked' => TRUE],
          ],
      ],
    ];

    if (\Drupal::service('module_handler')->moduleExists('webform_encrypt')) {
      $form['use_encrypt'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Decrypt values'),
        '#description' => $this->t('This only applies when Webform encrypt module is being used in one or more webform elements.'),
        '#default_value' => $this->entity->getEncryptionCheck(),
      ];

      // Select with all encryption profiles.
      $encryption_profiles = WebformContentCreatorUtilities::getFormattedEncryptionProfiles();
      $form['encryption_profile'] = [
        '#type' => 'select',
        '#title' => $this->t('Encryption profile'),
        '#options' => $encryption_profiles,
        '#default_value' => $this->entity->getEncryptionProfile(),
        '#description' => $this->t("Encryption profile name"),
        '#states' => [
          'visible' =>
            [
              ':input[name="use_encrypt"]' => ['checked' => TRUE],
            ],
          'required' =>
            [
              ':input[name="use_encrypt"]' => ['checked' => TRUE],
            ],
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$this->entity->equalsWebform($form['webform']['#default_value']) || !$this->entity->equalsBundle($form['target_bundle']['#default_value']) || !$this->entity->equalsEntityType($form['target_entity_type']['#default_value'])) {
      $this->entity->set('elements', []);
    }
    $status = $this->entity->save();
    $this->entity->statusMessage($status);
    $form_state->setRedirect('entity.webform_content_creator.collection');
  }

  /**
   * Helper function to check whether a Webform content creator entity exists.
   *
   * @param string $id
   *   Entity id.
   *
   * @return bool
   *   True if entity already exists.
   */
  public function exist($id) {
    return WebformContentCreatorUtilities::existsWebformContentCreatorEntity($id);
  }

  /**
   * 
   */
  public function getBundles(array &$element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $value = $triggering_element['#value'];
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($value);
    foreach ($bundles as $key => $value) {
       $options[$key] = $value['label'] ;
    }
    $wrapper_id = $triggering_element["#ajax"]["wrapper"];
    $rendered_field = '';
    foreach ($options as $key => $value) {
      $rendered_field .= "<option value='". $key . "'>" . $value . "</option>";
    }
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand("#" . $wrapper_id, $rendered_field));
    return $response;
  }

}
