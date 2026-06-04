<?php

declare(strict_types=1);

namespace Drupal\name\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * Loads full user when user_format_name_alter needs preferred name field data.
 *
 * @internal
 */
class UserRealnamePreloadService implements UserRealnamePreloadInterface {

  /**
   * Whether a preload operation is in progress (recursion guard).
   */
  private bool $inPreload = FALSE;

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Preloads user entity where configured; mirrors legacy global behavior.
   *
   * Recursion check in place after RealName module issue queue suggested that
   * there were issues with token based recursion on load.
   */
  public function preload(AccountInterface $account): void {
    if (!$this->inPreload && !isset($account->realname)) {
      $field_name = $this->configFactory->get('name.settings')->get('user_preferred');
      if ($field_name && FieldConfig::loadByName('user', 'user', $field_name)) {
        $this->inPreload = TRUE;
        $id = $account->id();
        $account = $id ? $this->entityTypeManager->getStorage('user')->load($id) : 'unknown';
        $this->inPreload = FALSE;
      }
    }
  }

}
