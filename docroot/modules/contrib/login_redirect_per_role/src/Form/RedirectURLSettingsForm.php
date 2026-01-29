<?php

namespace Drupal\login_redirect_per_role\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Utility\Token;
use Drupal\login_redirect_per_role\LoginRedirectPerRoleInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for configuring redirects per role.
 */
class RedirectURLSettingsForm extends ConfigFormBase {

  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $pathValidator
   *   The path validator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\path_alias\AliasManagerInterface $aliasManager
   *   The alias manager service.
   * @param \Drupal\login_redirect_per_role\LoginRedirectPerRoleInterface $loginRedirectPerRole
   *   The login redirect per role service.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    TypedConfigManagerInterface $typedConfigManager,
    private readonly PathValidatorInterface $pathValidator,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly Token $token,
    private readonly AliasManagerInterface $aliasManager,
    private readonly LoginRedirectPerRoleInterface $loginRedirectPerRole,
  ) {
    parent::__construct($configFactory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('path.validator'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('token'),
      $container->get('path_alias.manager'),
      $container->get('login_redirect_per_role.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'login_redirect_per_role.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'redirect_url_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('login_redirect_per_role.settings');
    $actions = $this->getAvailableActions();
    $roles = $this->getAvailableUserRoleNames();

    if ($this->moduleHandler->moduleExists('token')) {
      $form['token_tree'] = [
        '#theme' => 'token_tree_link',
      ];
    }

    foreach ($actions as $action_id => $action_label) {
      $holder_id = $action_id . '_holder';

      $form[$holder_id] = [
        '#type' => 'details',
        '#title' => $action_label,
        '#open' => TRUE,
        '#suffix' => '<br>',
      ];

      $form[$holder_id][$action_id] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Role'),
          $this->t('Redirect URL'),
          $this->t('Allow destination'),
          $this->t('Weight'),
        ],
        '#caption' => $this->t("If you don't need @action functionality - leave Redirect URLs empty.", ['@action' => $action_label]),
        '#empty' => $this->t('Sorry, There are no items!'),
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'table-sort-weight',
          ],
        ],
      ];

      foreach ($roles as $role_id => $role_name) {
        $row = $config->get($action_id . '.' . $role_id);

        $form[$holder_id][$action_id][$role_id]['#attributes']['class'][] = 'draggable';
        $form[$holder_id][$action_id][$role_id]['#weight'] = $row['weight'] ?? 0;

        $form[$holder_id][$action_id][$role_id]['role'] = [
          '#markup' => $role_name,
        ];
        $form[$holder_id][$action_id][$role_id]['redirect_url'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Redirect URL'),
          '#title_display' => 'invisible',
          '#default_value' => $row['redirect_url'] ?? '',
        ];

        // When a token is entered, check if the token is valid.
        if ($this->moduleHandler->moduleExists('token')) {
          $form[$holder_id][$action_id][$role_id]['redirect_url']['#element_validate'][] = 'token_element_validate';
          $form[$holder_id][$action_id][$role_id]['redirect_url']['#after_build'][] = 'token_element_validate';
          $form[$holder_id][$action_id][$role_id]['redirect_url']['#token_types'] = [];
        }

        $form[$holder_id][$action_id][$role_id]['allow_destination'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Allow destination'),
          '#title_display' => 'invisible',
          '#default_value' => $row['allow_destination'] ?? FALSE,
        ];
        $form[$holder_id][$action_id][$role_id]['weight'] = [
          '#type' => 'weight',
          '#title' => $this->t('Weight for @role', ['@role' => $role_name]),
          '#title_display' => 'invisible',
          '#default_value' => $form[$holder_id][$action_id][$role_id]['#weight'],
          '#attributes' => ['class' => ['table-sort-weight']],
        ];
      }

      Element::children($form[$holder_id][$action_id], TRUE);
    }

    $form['hint'] = [
      '#type' => 'details',
      '#title' => $this->t('Working logic'),
      '#description' => $this->t('Roles order in list is their priorities: higher in list - higher priority.<br>For example: You set roles ordering as:<br>+ Admin<br>+ Manager<br>+ Authenticated<br>it means that when some user log in (or log out) module will check:<br><em>Does this user have Admin role?</em><ul><li>Yes and Redirect URL is not empty - redirect to related URL</li><li>No or Redirect URL is empty:</li></ul><em>Does this user have Manager role?</em><ul><li>Yes and Redirect URL is not empty - redirect to related URL</li><li>No or Redirect URL is empty:</li></ul><em>Does this user have Authenticated role?</em><ul><li>Yes and Redirect URL is not empty - redirect to related URL</li><li>No or Redirect URL is empty - use default Drupal action</li></ul>'),
      '#open' => FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $roles = $this->getAvailableUserRoleNames();
    $actions = $this->getAvailableActions();

    foreach ($actions as $action_id => $action_label) {
      foreach ($form_state->getValue($action_id) as $role_id => $settings) {
        if (empty($settings['redirect_url'])) {
          continue;
        }

        $redirect_url = $settings['redirect_url'];

        // Checks if the redirect URL equals <front> or starts with
        // '/', '?', '#' or '['.
        if ($redirect_url !== '<front>' && !in_array($redirect_url[0], ['/', '?', '#', '['])) {
          $form_state->setErrorByName(
            $action_id . '][' . $role_id . '][redirect_url',
            $this->t(
              '<strong>@action:</strong> Redirect URL for "@role" role must begin with "/", "?" or "#".',
              ['@action' => $action_label, '@role' => $roles[$role_id]]
            )
          );
          continue;
        }

        $path = $this->token->replace($redirect_url);
        $is_token = $path !== $redirect_url;

        $path = $this->loginRedirectPerRole->stripSubdirectoryFromPath($path);
        $path = $this->aliasManager->getPathByAlias($path);

        if (!$is_token) {
          $form_state->setValue([$action_id, $role_id, 'redirect_url'], $path);
        }

        if (!$this->pathValidator->isValid($path)) {
          $form_state->setErrorByName(
            $action_id . '][' . $role_id . '][redirect_url',
            $this->t(
              '<strong>@action:</strong> Redirect URL for "@role" role is invalid or you do not have access to it.',
              ['@action' => $action_label, '@role' => $roles[$role_id]]
            )
          );
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $config = $this->config('login_redirect_per_role.settings');

    foreach (array_keys($this->getAvailableActions()) as $actionId) {
      $config->set($actionId, $form_state->getValue($actionId));
    }

    $config->save();
  }

  /**
   * Return available user role names keyed by role id.
   *
   * @return array<string, string>
   *   Available user role names.
   */
  protected function getAvailableUserRoleNames(): array {
    $names = [];

    /** @var \Drupal\user\RoleInterface[] $roles */
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();

    unset($roles[RoleInterface::ANONYMOUS_ID]);

    foreach ($roles as $role) {
      $names[$role->id()] = $role->label();
    }

    return $names;
  }

  /**
   * Return available actions.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
   *   Available actions.
   */
  protected function getAvailableActions(): array {
    return [
      'login' => $this->t('Login redirect'),
      'logout' => $this->t('Logout redirect'),
    ];
  }

}
