<?php

namespace Drupal\login_destination\Form;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\login_destination\Entity\LoginDestination;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base for controller for login destination add/edit forms.
 */
class LoginDestinationRuleForm extends EntityForm {

  use StringTranslationTrait;

  /**
   * The login destination entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $loginDestinationStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a base class for login destination add and edit forms.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $login_destination_storage
   *   The login destination entity storage.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityStorageInterface $login_destination_storage, LanguageManagerInterface $language_manager) {
    $this->loginDestinationStorage = $login_destination_storage;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('login_destination'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\login_destination\Entity\LoginDestination $login_destination */
    $login_destination = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $login_destination->label(),
      '#description' => $this->t('A short description of this login destination rule.'),
      '#required' => TRUE,
    ];

    $form['name'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [$this->loginDestinationStorage, 'load'],
      ],
      '#disabled' => !$login_destination->isNew(),
      '#default_value' => $login_destination->id(),
      '#required' => TRUE,
      '#description' => $this->t('A unique machine-readable name for this login destination rule.'),
    ];

    $form['triggers'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Redirect upon triggers'),
      '#options' => [
        LoginDestination::TRIGGER_REGISTRATION => $this->t('Registration'),
        LoginDestination::TRIGGER_LOGIN => $this->t('Login'),
        LoginDestination::TRIGGER_ONE_TIME_LOGIN => $this->t('One-time login link'),
        LoginDestination::TRIGGER_LOGOUT => $this->t('Logout'),
      ],
      '#required' => TRUE,
      '#default_value' => !empty($login_destination->triggers) ? $login_destination->getTriggers() : [],
      '#description' => $this->t('Redirect only upon selected trigger(s).'),
    ];

    $form['destination_path'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#placeholder' => '',
      '#attributes' => [
        'data-autocomplete-first-character-blacklist' => '/#?[',
      ],
      '#title' => $this->t('Redirect destination'),
      '#default_value' => $this->getUriAsDisplayableString($login_destination->getDestination()),
      '#element_validate' => [[$this, 'validateUriElement']],
      '#maxlength' => 2048,
      '#required' => TRUE,
      '#process_default_value' => FALSE,
      '#description' => $this->t('Start typing the title of a piece of content to select it. You can also enter an internal path such as %add-node or an external URL such as %url. Enter %front to link to the front page. Enter %current to link to a current page.', [
        '%front' => '<front>',
        '%current' => '<current>',
        '%add-node' => '/node/add',
        '%url' => 'http://example.com',
      ]),
    ];

    // Add the token tree UI.
    $form['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user'],
      '#show_restricted' => TRUE,
      '#global_types' => TRUE,
    ];

    $form['pages_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Redirect from specific pages'),
      '#default_value' => $login_destination->getPagesType(),
      '#options' => [
        $login_destination::REDIRECT_NOT_LISTED => $this->t('All pages except those listed'),
        $login_destination::REDIRECT_LISTED => $this->t('Only the listed pages'),
      ],
    ];

    $form['pages'] = [
      '#type' => 'textarea',
      '#default_value' => $login_destination->getPages(),
      '#description' => $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page. %login is the login form. %register is the registration form. %reset is the one-time login (e-mail validation).", [
        '%blog' => 'blog',
        '%blog-wildcard' => '/blog/*',
        '%front' => '<front>',
        '%login' => '/user',
        '%register' => '/user/register',
        '%reset' => '/user/*/edit',
      ]),
    ];

    $languages[''] = $this->t('All languages');
    foreach ($this->languageManager->getLanguages() as $key => $value) {
      $languages[$key] = $value->getName();
    }
    $form['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Redirect for language'),
      '#options' => $languages,
      '#default_value' => $login_destination->getLanguage(),
      '#description' => $this->t('Redirect only for the selected language.'),
    ];

    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Redirect users with roles'),
      '#options' => $login_destination->getAllSystemRoles(),
      '#default_value' => $login_destination->getRoles(),
      '#description' => $this->t('Redirect only the selected role(s). If you select no roles, all users will be redirected.'),
    ];

    $form['uuid'] = [
      '#type' => 'value',
      '#value' => $login_destination->get('uuid'),
    ];

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Get selected roles.
    $roles = array_filter($form_state->getValue('roles'));

    // Set filtered values.
    $form_state->setValue('roles', $roles);
    $form_state->setValue('triggers', array_filter($form_state->getValue('triggers')));

    // Get entered by user destination path.
    // $destination = $form_state->getValue('destination_path');.
    // @todo verify that selected role has access to entered path.
    // @todo verify entered paths to specific pages.
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var  \Drupal\login_destination\Entity\LoginDestination $login_destination */
    $login_destination = $this->entity;

    if ($login_destination->save()) {
      $this->messenger()->addMessage($this->t('Saved the %label login destination.', [
        '%label' => $login_destination->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The %label login destination was not saved.', [
        '%label' => $login_destination->label(),
      ]));
    }
    $form_state->setRedirect('login_destination.list');
  }

  /**
   * Form element validation handler for the 'uri' element.
   *
   * Disallows saving inaccessible or untrusted URLs.
   *
   * @see LinkWidget::validateUriElement()
   */
  public function validateUriElement($element, FormStateInterface $form_state, $form) {
    $uri = $this->getUserEnteredStringAsUri($element['#value']);
    $form_state->setValueForElement($element, $uri);

    // If getUserEnteredStringAsUri() mapped the entered value to a 'internal:'
    // URI , ensure the raw value begins with '/', '?' or '#'.
    // @todo '<front>' is valid input for BC reasons, may be removed by
    //   https://www.drupal.org/node/2421941
    if (parse_url($uri, PHP_URL_SCHEME) === 'internal'
      && !in_array(
        $element['#value'][0], [
          '/',
          '?',
          '#',
          '[',
        ],
        TRUE
      )
      && substr($element['#value'], 0, 7) !== '<front>'
      && substr($element['#value'], 0, 9) !== '<current>'
    ) {
      $form_state->setError($element, $this->t('Manually entered paths should start with /, [, ? or #.'));
      return;
    }
  }

  /**
   * Gets the URI without the 'internal:' or 'entity:' scheme.
   *
   * The following two forms of URIs are transformed:
   * - 'entity:' URIs: to entity autocomplete ("label (entity id)") strings;
   * - 'internal:' URIs: the scheme is stripped.
   *
   * This method is the inverse of ::getUserEnteredStringAsUri().
   *
   * @param string $uri
   *   The URI to get the displayable string for.
   *
   * @return string
   *   Returns a $displayable_string for the URI.
   *
   * @see LinkWidget::getUriAsDisplayableString()
   */
  protected function getUriAsDisplayableString($uri) {
    if (!$uri) {
      return '';
    }
    $scheme = parse_url($uri, PHP_URL_SCHEME);

    // By default, the displayable string is the URI.
    $displayable_string = $uri;

    // A different displayable string may be chosen in case of the 'internal:'
    // or 'entity:' built-in schemes.
    if ($scheme === 'internal') {
      $uri_reference = explode(':', $uri, 2)[1];

      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      $path = parse_url($uri, PHP_URL_PATH);
      if ($path === '/') {
        $uri_reference = '<front>' . substr($uri_reference, 1);
      }

      $displayable_string = $uri_reference;
    }
    elseif ($scheme === 'entity') {
      list($entity_type, $entity_id) = explode('/', substr($uri, 7), 2);
      // Show the 'entity:' URI as the entity autocomplete would.
      if ($this->entityTypeManager->getDefinition($entity_type, FALSE) && $entity = $this->entityTypeManager
        ->getStorage($entity_type)
        ->load($entity_id)
      ) {
        $displayable_string = EntityAutocomplete::getEntityLabels([$entity]);
      }
    }

    return $displayable_string;
  }

  /**
   * Gets the user-entered string as a URI.
   *
   * The following two forms of input are mapped to URIs:
   * - entity autocomplete ("label (entity id)") strings: to 'entity:' URIs;
   * - strings without a detectable scheme: to 'internal:' URIs.
   *
   * This method is the inverse of ::getUriAsDisplayableString().
   *
   * @param string $string
   *   The user-entered string.
   *
   * @return string
   *   The URI, if a non-empty $uri was passed.
   *
   * @see LinkWidget::getUserEnteredStringAsUri()
   */
  protected function getUserEnteredStringAsUri($string) {
    // By default, assume the entered string is an URI.
    $uri = $string;

    // Detect entity autocomplete string, map to 'entity:' URI.
    $entity_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($string);
    if ($entity_id !== NULL) {
      // @todo Support entity types other than 'node'. Will be fixed in
      //   https://www.drupal.org/node/2423093.
      $uri = 'entity:node/' . $entity_id;
    }
    // Detect a schemeless string, map to 'internal:' URI.
    elseif (!empty($string) && parse_url($string, PHP_URL_SCHEME) === NULL) {
      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      // - '<front>' -> '/'
      // - '<front>#foo' -> '/#foo'
      if (strpos($string, '<front>') === 0) {
        $string = '/' . substr($string, strlen('<front>'));
      }
      if (strpos($string, '[') === 0) {
        $string = '/' . $string;
      }
      $uri = 'internal:' . $string;
    }

    return $uri;
  }

}
