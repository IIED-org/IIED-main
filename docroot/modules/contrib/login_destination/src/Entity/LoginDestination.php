<?php

namespace Drupal\login_destination\Entity;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\login_destination\LoginDestinationInterface;

/**
 * Defines a login destination configuration entity.
 *
 * @ConfigEntityType(
 *   id = "login_destination",
 *   label = @Translation("Login Destination"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\login_destination\Form\LoginDestinationRuleForm",
 *       "edit" = "Drupal\login_destination\Form\LoginDestinationRuleForm",
 *       "delete" = "Drupal\login_destination\Form\LoginDestinationDeleteRuleForm",
 *     },
 *     "list_builder" = "Drupal\login_destination\Controller\LoginDestinationListBuilder",
 *   },
 *   admin_permission = "administer login destination settings",
 *   config_prefix = "destination",
 *   entity_keys = {
 *     "id" = "name",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/people/login-destination/{login_destination}/edit",
 *     "delete-form" = "/admin/config/people/login-destination/{login_destination}/delete",
 *   },
 *   config_export = {
 *     "name",
 *     "label",
 *     "triggers",
 *     "roles",
 *     "pages_type",
 *     "pages",
 *     "language",
 *     "enabled",
 *     "destination_path",
 *     "weight"
 *   }
 * )
 */
class LoginDestination extends ConfigEntityBase implements LoginDestinationInterface {

  use StringTranslationTrait;

  /**
   * Redirect from all pages except listed.
   */
  const REDIRECT_NOT_LISTED = 0;

  /**
   * Redirect from only the listed pages.
   */
  const REDIRECT_LISTED = 1;

  /**
   * Login trigger.
   */
  const TRIGGER_LOGIN = 'login';

  /**
   * Registration trigger.
   */
  const TRIGGER_REGISTRATION = 'registration';

  /**
   * One-time login trigger.
   */
  const TRIGGER_ONE_TIME_LOGIN = 'one-time-login';

  /**
   * Logout trigger.
   */
  const TRIGGER_LOGOUT = 'logout';

  /**
   * The login destination machine name.
   *
   * @var string
   */
  public $name;

  /**
   * The login destination short description.
   *
   * @var string
   */
  public $label;

  /**
   * The login destination triggers.
   *
   * @var array
   */
  public $triggers = [];

  /**
   * The login destination roles.
   *
   * @var array
   */
  public $roles = [];

  /**
   * The login destination pages type.
   *
   * @var int
   */
  public $pages_type = self::REDIRECT_NOT_LISTED;

  /**
   * The login destination pages.
   *
   * @var string
   */
  public $pages = '';

  /**
   * The login destination language.
   *
   * @var string
   */
  public $language = '';

  /**
   * Status.
   *
   * @var bool
   */
  public $enabled = TRUE;

  /**
   * The login destination destination.
   *
   * @var string
   */
  public $destination_path;

  /**
   * The login destination weight.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getTriggers() {
    return $this->triggers;
  }

  /**
   * {@inheritdoc}
   */
  public function getDestination() {
    return $this->destination_path;
  }

  /**
   * {@inheritdoc}
   */
  public function getPagesType() {
    return $this->pages_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getPages() {
    return $this->pages;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguage() {
    return $this->language;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles() {
    return $this->roles;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function viewTriggers() {
    $items = [];
    foreach ($this->triggers as $trigger) {
      if (empty($trigger)) {
        continue;
      }
      switch ($trigger) {
        case LoginDestination::TRIGGER_REGISTRATION:
          $items[] = $this->t('Registration');
          break;

        case LoginDestination::TRIGGER_LOGIN:
          $items[] = $this->t('Login');
          break;

        case LoginDestination::TRIGGER_ONE_TIME_LOGIN:
          $items[] = $this->t('One-time login link');
          break;

        case LoginDestination::TRIGGER_LOGOUT:
          $items[] = $this->t('Logout');
          break;
      }
    }
    return $this->renderItemList($items, $this->t('All triggers'));
  }

  /**
   * {@inheritdoc}
   */
  public function viewRoles() {
    $roles = $this->getAllSystemRoles();
    $items = array_values(array_intersect_key($roles, $this->roles));
    return $this->renderItemList($items, $this->t('All roles'));
  }

  /**
   * {@inheritdoc}
   */
  public function viewPages() {
    $type = $this->pages_type;
    $pages = trim($this->pages);

    if (empty($pages)) {
      if ($type == self::REDIRECT_NOT_LISTED) {
        return $this->t('All pages');
      }
      return $this->t('No pages');
    }

    $pages = explode("\n", preg_replace('/\r/', '', $this->pages));
    $items = [];
    foreach ($pages as $page) {
      $items[] = $type == self::REDIRECT_NOT_LISTED ? '~ ' . $page : $page;
    }

    return $this->renderItemList($items, $this->t('Empty'));
  }

  /**
   * {@inheritdoc}
   */
  public function viewDestination() {
    if ($this->isDestinationCurrent()) {
      $scheme = 'internal';
    }
    else {
      $url = Url::fromUri($this->destination_path);
      $label = $this->destination_path;
      if ($url->isExternal()) {
        return Html::escape($label);
      }
      $scheme = parse_url($this->destination_path, PHP_URL_SCHEME);
    }
    if ($scheme === 'internal') {
      return $this->t('Internal destination');
    }
    if ($scheme === 'entity') {
      $params = $url->getRouteParameters();
      $entity = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load(reset($params));
      return $entity->get('title')->value;
    }

    return Html::escape($this->destination_path);
  }

  /**
   * Check is destination path links to a current page or not.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  public function isDestinationCurrent() {
    return mb_strpos($this->destination_path, '<current>') !== FALSE;
  }

  /**
   * Render item list.
   *
   * @param array $array
   *   List of items.
   * @param string $empty_message
   *   Default empty message.
   *
   * @return string|\Drupal\Core\Render\Markup
   *   List of items or empty message.
   */
  protected function renderItemList(array $array, $empty_message) {
    $items = [];
    foreach ($array as $value) {
      if (!empty($value)) {
        $items[] = Html::escape($value);
      }
    }

    if (count($items) === 0) {
      return $empty_message;
    }

    $item_list = [
      '#theme' => 'item_list',
      '#items' => $items,
      '#list_type' => 'ul',
    ];
    return \Drupal::service('renderer')->render($item_list);
  }

  /**
   * Get all roles in the system.
   *
   * @return array
   *   List of system roles.
   */
  public function getAllSystemRoles() {
    $role_options = [];
    foreach (user_roles(TRUE) as $role) {
      $role_options[$role->id()] = $role->label();
    }
    return $role_options;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->enabled;
  }

}
