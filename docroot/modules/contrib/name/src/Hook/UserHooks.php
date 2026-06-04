<?php

declare(strict_types=1);

namespace Drupal\name\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\name\Service\AdditionalComponentInterface;
use Drupal\name\Service\NameFormatterInterface;
use Drupal\name\Service\UserRealnamePreloadInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Hook implementations that integrate the user entity with realname lookups.
 *
 * @internal
 */
final class UserHooks {

  /**
   * Static cache of the preferred name FieldConfig for the user entity.
   *
   * Resolved once per request because it is keyed on a config value.
   */
  private ?FieldConfig $preferredField = NULL;

  /**
   * Whether the preferred field has been resolved (even to NULL).
   */
  private bool $preferredFieldResolved = FALSE;

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    #[Autowire(lazy: true)]
    private readonly NameFormatterInterface $nameFormatter,
    #[Autowire(lazy: true)]
    private readonly AdditionalComponentInterface $additionalComponent,
    private readonly UserRealnamePreloadInterface $realnamePreload,
  ) {}

  /**
   * Implements hook_user_format_name_alter().
   */
  // phpcs:ignore Drupal.Commenting.PostStatementComment.Found -- #[Hook] is a PHP attribute, not a trailing comment.
  #[Hook('user_format_name_alter')] // @phpstan-ignore attribute.notFound
  public function userFormatNameAlter(&$name, AccountInterface $account): void {
    // Don't alter anonymous users or objects that do not have any user ID.
    if ($account->isAnonymous()) {
      return;
    }

    // Try and load the realname in case this is a partial user object or
    // another object, such as a node or comment.
    if (!isset($account->realname)) {
      $this->realnamePreload->preload($account);
    }

    // Since $account may not be the real User entity object, check the name
    // lookup cache for results too.
    if (!isset($account->realname) || !mb_strlen($account->realname)) {
      $names = &drupal_static('name_user_realname_cache', []);
      if (isset($names[$account->id()])) {
        $account->realname = $names[$account->id()];
      }
    }

    if (isset($account->realname) && mb_strlen($account->realname)) {
      $name = $account->realname;
    }
  }

  /**
   * Implements hook_user_load().
   */
  // phpcs:ignore Drupal.Commenting.PostStatementComment.Found -- #[Hook] is a PHP attribute, not a trailing comment.
  #[Hook('user_load')] // @phpstan-ignore attribute.notFound
  public function userLoad(array $users): void {
    // In the event there are a lot of user_load() calls, cache the results.
    $names = &drupal_static('name_user_realname_cache', []);

    $field = $this->resolvePreferredField();
    if (!$field) {
      return;
    }

    foreach ($users as $account) {
      $uid = $account->id();
      if (isset($names[$uid])) {
        $users[$uid]->realname = $names[$uid];
        continue;
      }
      if (!$account->hasField($field->getName()) || $account->get($field->getName())->isEmpty()) {
        continue;
      }
      $components = $account->get($field->getName())->get(0)->getValue();
      foreach (['preferred', 'alternative'] as $key) {
        $key_value = $field->getSetting($key . '_field_reference');
        if (!$key_value) {
          continue;
        }
        $sep_value = $field->getSetting($key . '_field_reference_separator');
        $value = $this->additionalComponent->getAdditionalComponent(
          $account->get($field->getName()),
          $key_value,
          $sep_value,
        );
        if ($value) {
          $components[$key] = $value;
        }
      }
      $names[$uid] = $this->nameFormatter->format(
        $components,
        $field->getSetting('override_format'),
      );
      $users[$uid]->realname = $names[$uid];
    }
  }

  /**
   * Resolves the configured preferred user name field once per request.
   */
  private function resolvePreferredField(): ?FieldConfig {
    if ($this->preferredFieldResolved) {
      return $this->preferredField;
    }
    $field_name = $this->configFactory->get('name.settings')->get('user_preferred');
    $this->preferredField = $field_name
      ? FieldConfig::loadByName('user', 'user', $field_name)
      : NULL;
    $this->preferredFieldResolved = TRUE;
    return $this->preferredField;
  }

}
