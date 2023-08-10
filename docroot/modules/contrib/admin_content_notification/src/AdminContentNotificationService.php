<?php

namespace Drupal\admin_content_notification;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * AdminContentNotificationService implement helper service class.
 */
class AdminContentNotificationService {

  use StringTranslationTrait;
  use LoggerChannelTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The mail manager instance.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The link generator instance.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * Module Handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a verbose messenger.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    AccountInterface $account, MailManagerInterface $mailManager,
    LinkGeneratorInterface $linkGenerator,
    ModuleHandlerInterface $moduleHandler,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->configFactory = $config_factory;
    $this->account = $account;
    $this->mailManager = $mailManager;
    $this->linkGenerator = $linkGenerator;
    $this->moduleHandler = $moduleHandler;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Get settings of admin content notification.
   */
  public function getConfigs() {
    return $this->configFactory->get('admin_content_notification.settings');
  }

  /**
   * Get users of roles.
   *
   * @return array
   *   Array of User Uids.
   */
  public function getUsersOfRoles($roles) {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $ids = $user_storage->getQuery()
      ->condition('status', 1)
      ->condition('roles', $roles, 'IN')
      ->accessCheck(FALSE)
      ->execute();
    if (in_array('authenticated', $roles)) {
      $ids_authenticated = $user_storage->getQuery()
        ->condition('status', 1)
        ->accessCheck(FALSE)
        ->execute();
      $ids = array_unique(array_merge($ids, $ids_authenticated));
    }
    return $ids;
  }

  /**
   * Check if current user allowed to send admin content notification.
   *
   * @return bool
   *   Return true if allowed to send admin content notification.
   */
  public function isCurrentUserRoleAllowedToSendNotification() {
    $roles = $this->account->getRoles();
    $trigger_for_roles = ($this->getConfigs()->get('admin_content_notification_allowed_roles')) ?: [];
    return count(array_intersect(array_filter($trigger_for_roles), $roles));
  }

  /**
   * Send Email.
   *
   * @param Drupal\node\NodeInterface $node
   * @param bool $is_new
   */
  public function sendMail(NodeInterface $node, $is_new = FALSE) {
    global $base_url;
    $config = $this->getConfigs();
    $site_config =  $this->configFactory->get('system.site');
    $node_type = $node->getType();
    $node_type_label = $node->type->entity->label();
    // Checking if the nodetype is the one selected.
    $selected_node_types = $config->get('admin_content_notification_node_types');
    if (count($selected_node_types) && in_array($node_type, $selected_node_types, TRUE)) {
      // Check if limiting based on node status.
      $selected_node_status = $config->get('admin_content_notification_trigger_on_node_status');
      if ($selected_node_status > 0) {
        $node_published = $node->isPublished();
        // Don't notify of published nodes.
        if ($node_published && $selected_node_status == 2) {
          return;
        }
        // Don't notify of unpublished nodes.
        elseif (!$node_published && $selected_node_status == 1) {
          return;
        }
      }
      $user = $is_new ? $node->getOwner() : $node->getRevisionUser();
      $user_name = $user->getDisplayName();
      $url = Url::fromUri($base_url . '/node/' . $node->id());
      $internal_link = $this->linkGenerator->generate($this->t('@title', ['@title' => $node->label()]), $url);
      $variables = [
        '@user_who_posted' => $user_name,
        '@content_link' => $internal_link,
        '@content_title' => $node->label(),
        '@content_type' => $node_type_label,
        '@action' => $is_new ? $this->t('posted') : $this->t('updated'),
      ];
      $subject = $this->t($config->get('admin_content_notification_email_subject'), $variables);
      $body = $this->t($config->get('admin_content_notification_email_body'), $variables);
      if ($this->isTokenEnabled()) {
        $token_service = \Drupal::token();
        // Replace the token for body.
        $body = $token_service->replace($body, [
          'node' => $node,
          'user' => $user,
        ], ['clear' => TRUE]);
        $subject = $token_service->replace($subject, [
          'node' => $node,
          'user' => $user,
        ], ['clear' => TRUE]);
      }
      $recepients_emails = $config->get('admin_content_notification_email');
      if (empty($recepients_emails)) {
        $roles_notify = array_keys(array_filter($config->get('admin_content_notification_roles_notified')));
        $ids = !empty($roles_notify) ? $this->getUsersOfRoles($roles_notify) : [];
        $emails = [];
        if (count($ids)) {
          $users = User::loadMultiple($ids);
          foreach ($users as $userload) {
            $emails[] = $userload->getEmail();
          }
        }
        $recepients_emails = implode(',', $emails);
      }

      // Set default to address as other will be passed as bcc.
      $to = !empty($config->get('admin_content_notification_default_to')) ? $config->get('admin_content_notification_default_to') : $site_config->get('mail');
      $params = [
        'body' => $body,
        'subject' => $subject,
        'nid' => $node->id(),
      ];

      // Allow to alter $recepients_emails
      // by using hook_admin_content_notification_recipients_alter().
      // @see admin_content_notification.api.php
      $this->moduleHandler
        ->alter('admin_content_notification_recipients', $recepients_emails, $node);

      if (!empty($recepients_emails)) {
        $params['bcc'] = $recepients_emails;
      }

      // Allow to alter $params
      // by using hook_admin_content_notification_params_alter().
      // @see admin_content_notification.api.php
      $this->moduleHandler
        ->alter('admin_content_notification_params', $params, $node);

      if (strlen($recepients_emails) === 0) {
        return;
      }
      $key = 'admin_content_notification_key';
      if (empty($to) || empty($site_config->get('mail'))) {
        $this->getLogger('admin_content_notification')->error($this->t('From and To email addresses should not be empty.'));
        return;
      }
      $this->mailManager->mail('admin_content_notification', $key, $to, 'en', $params, $site_config->get('mail'), TRUE);
      $this->getLogger('admin_content_notification')->notice($this->t('Admin content notification sent to @emails.', ['@emails' => $recepients_emails]));
    }
  }

  /**
   * Check if token module enabled.
   *
   * @return bool
   *   Return True if enabled.
   */
  public function isTokenEnabled() {
    return $this->moduleHandler->moduleExists('token');
  }

}
