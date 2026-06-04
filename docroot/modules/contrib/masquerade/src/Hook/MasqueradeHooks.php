<?php

declare(strict_types=1);

namespace Drupal\masquerade\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\masquerade\Masquerade;
use Drupal\user\UserInterface;

/**
 * Hook implementations for masquerade.
 */
class MasqueradeHooks {
  use StringTranslationTrait;

  public function __construct(
    protected Masquerade $masquerade,
  ) {}

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.masquerade':
        $output = '';
        $output .= '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('The Masquerade module allows users to temporarily switch to another user account. It records the original user account, so users can easily switch back.') . '</p>';
        $output .= '<h3>' . $this->t('Uses') . '</h3>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Granting masquerade access') . '</dt>';
        $output .= '<dd>' . $this->t('Users may only masquerade as another user if they have the <a href=":permission_link">Masquerade as any user</a> permission or if they have all the <a href=":permission_link">Masquerade as ROLE</a> permissions for all the target account\'s roles.', [
          ':permission_link' => Url::fromRoute('user.admin_permissions', [], ['fragment' => 'module-masquerade'])->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Masquerading as another user') . '</dt>';
        $output .= '<dd>' . $this->t('There are multiple ways to masquerade as another user:');
        $output .= '<ul>';
        $output .= '<li>' . $this->t('On the <a href=":admin-people-url">administrative user listing</a>, choose the <em>Masquerade</em> operation of a certain user account.', [
          ':admin-people-url' => Url::fromRoute('entity.user.collection')->toString(),
        ]) . '</li>';
        $output .= '<li>' . $this->t('Masquerade can be used directly from menus provided by the %toolbar module.', [
          '%toolbar' => $this->t('Toolbar'),
        ]) . '</li>';
        $output .= '</ul>';
        $output .= '</dd>';
        $output .= '<dt>' . $this->t('Switching back') . '</dt>';
        $output .= '<dd>' . $this->t('To stop masquerading as another user, click the <em>Unmasquerade</em> link in the user account links menu.') . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_toolbar().
   *
   * @todo Nest this with the "View profile", "Edit profile", and "Log out"
   *   links under the username tab.
   */
  #[Hook('toolbar')]
  public function toolbar(): array {
    $items = [
      'masquerade_switch_back' => [
        '#cache' => [
          'contexts' => ['session.is_masquerading'],
        ],
      ],
    ];
    if ($this->masquerade->isMasquerading()) {
      $items['masquerade_switch_back'] += [
        '#type' => 'toolbar_item',
        'tab' => [
          '#type' => 'link',
          '#title' => $this->t('Unmasquerade'),
          '#url' => Url::fromRoute('masquerade.unmasquerade'),
        ],
        // Hopefully shows immediately after the username tab.
        '#weight' => 101,
      ];
    }
    return $items;
  }

  /**
   * Implements hook_masquerade_access().
   *
   * This default implementation only returns TRUE and never FALSE, since
   * alternative access implementations could not work otherwise.
   */
  #[Hook('masquerade_access')]
  public function masqueradeAccess($user, UserInterface $target_account): ?bool {
    // Uid 1 may masquerade as anyone.
    if ($user->id() == 1) {
      return TRUE;
    }
    // Uid 1 gets special treatment with its own permission.
    if ($target_account->id() == 1) {
      if ($user->hasPermission('masquerade as super user')) {
        return TRUE;
      }
      else {
        return NULL;
      }
    }
    // The current user must be allowed to masquerade.
    if ($user->hasPermission('masquerade as any user')) {
      return TRUE;
    }
    // Permissions may be granted on a per-role basis.
    $target_account_roles = $target_account->getRoles();
    foreach ($target_account_roles as $role_id) {
      if (!$user->hasPermission('masquerade as ' . $role_id)) {
        return NULL;
      }
    }

    // Only allow masquerade if user has access to all target account roles.
    return TRUE;
  }

}
