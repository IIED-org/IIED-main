<?php

namespace Drupal\delete_all\Commands;

use Drush\Commands\DrushCommands;
use Drupal\delete_all\Controller\ContentDeleteController;
use Drupal\delete_all\Controller\EntityDeleteController;
use Drupal\delete_all\Controller\UserDeleteController;
use Drush\Exceptions\UserAbortException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class DeleteAllCommands extends DrushCommands {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface;
   */
  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * Delete users.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases, config, etc.
   *
   * @option role
   *   pick roles
   * @usage drush delete-all-delete-users
   *   Delete all users.
   *
   * @command delete:all-delete-users
   * @aliases dadu,delete-all-delete-users
   */
  public function allDeleteUsers(array $options = ['role' => NULL]) {
    // Initialize $roles as FALSE to specify that all users should be deleted.
    // This will be overriden if user provides/choses a role.
    $input_roles = FALSE;

    $deleteUser = new UserDeleteController();

    // Check for presence of '--roles' in drush command.
    if ($options['role']) {
      // func_get_args collects all keywords separated by space in an array.
      // To get the roles, we join all the keywords in a string and then use
      // 'comma' to separate them.
      $types = $options['role'];
      if ($types != 1) {
        $input_roles = $types;
        if (strpos($input_roles, ',')) {
          $input_roles = explode(',', $input_roles);
        }
        else {
          $input_roles = [$input_roles];
        }
      }
      else {
        $choices = [];
        // Output all roles on screen and ask user to choose one.
        $roles = user_roles();
        foreach ($roles as $key => $value) {
          $choices[$key] = $value->label();
        }
        $role = $this->io()->choice(dt("Choose a role to delete."), $choices);

        // Return if no role is chosen.
        if ($role === 0) {
          return;
        }
        $input_roles = [$role];
      }
    }

    if ($this->io()->confirm('Are you sure you want to delete the users?')) {
      // Get users to delete.
      $users_to_delete = $deleteUser->getUserToDelete($input_roles);
      // Get batch array.
      $batch = $deleteUser->getUserDeleteBatch($users_to_delete);
      // Initialize the batch.
      batch_set($batch);
      // Start the batch process.
      drush_backend_batch_process();
    }
    else {
      throw new UserAbortException();
    }
  }

  /**
   * Delete content.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases, config, etc.
   *
   * @option type
   *   pick content type
   * @usage drush delete-all-delete-content
   *   Delete content.
   *
   * @command delete:all-delete-content
   * @aliases dadc,delete-all-delete-content
   */
  public function allDeleteContent(array $options = ['type' => NULL]) {
    // Initialize $content_type_options as FALSE to specify that all
    // content of all types should be deleted.
    // This will be overriden if user provides/choses a content type.
    $content_type_options = FALSE;

    $deleteContent = new ContentDeleteController();

    // Check for presence of '--type' in drush command.
    if ($options['type']) {
      // func_get_args() collects all keywords separated by space in an array.
      // To get the content types, we join all the keywords in a string and then
      // use 'comma' to separate them.
      $types = $options['type'];
      if ($types != 1) {
        $content_types = $types;
        if (strpos($content_types, ',')) {
          $content_type_options = explode(',', $content_types);
        }
        else {
          $content_type_options = [$content_types];
        }
      }
      // Output all content types on screen and ask user to choose one.
      else {
        $content_type_options = [];
        $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();

        foreach ($content_types as $content_type_machine_name => $content_type) {
          $choices[$content_type_machine_name] = $content_type->label();
        }

        $content_type_options = $this->io()->choice(dt("Choose a content type to delete. All contents of this"), $choices);

        // Return if no type is chosen.
        if ($content_type_options === 0) {
          return;
        }
        $content_type_options = [$content_type_options];
      }
    }

    if ($this->io()->confirm('Are you sure you want to delete the nodes?')) {
      // Get nodes to delete.
      $nodes_to_delete = $deleteContent->getContentToDelete($content_type_options);
      // Get batch array.
      $batch = $deleteContent->getContentDeleteBatch($nodes_to_delete);
      // Initialize the batch.
      batch_set($batch);
      // Start the batch process.
      drush_backend_batch_process();
    }
    else {
      throw new UserAbortException();
    }
  }

  /**
   * Delete entities.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases, config, etc.
   *
   * @option type
   *   pick entity type
   * @option bundle
   *   pick entity bundle
   * @usage drush delete-all-delete-entities
   *   Delete entities.
   *
   * @command delete:all-delete-entities
   * @aliases dade,delete-all-delete-entities
   */
  public function allDeleteEntities(array $options = ['type' => NULL, 'bundle' => NULL]) {
    // drush_delete_all_delete_entities($options['type'], $options['bundle']);
    // See bottom of https://weitzman.github.io/blog/port-to-drush9 for details on what to change when porting a
    // legacy command.
    // Get complete list of content entity types.
    $entities_info = [];
    $entities_info_extended = [];

    $entity_type_options = FALSE;
    $bundle_type_options = FALSE;

    foreach (\Drupal::entityTypeManager()->getDefinitions() as $id => $definition) {

      if (is_a($definition, 'Drupal\Core\Entity\ContentEntityType')) {
        $entities_info[$id] = $definition->getLabel();

        $entities_info_extended[$id] = [
          'label' => $definition->getLabel(),
          'entity_key_id' => $definition->getKeys()['id'],
          'entity_bundle' => $definition->getKeys()['bundle'],
        ];
      }
    }

    $deleteEntity = new EntityDeleteController();

    // Get variables.
    $vars = func_get_args();
    // Check for presence of '--type' in drush command.
    //
    echo drush_get_option('type');
    if ($options['type']) {

      $entity_type_options = $options['type'];

      if (!in_array($entity_type_options, array_keys($entities_info))) {
        drush_set_error('Please select a valid entity type');
        return;
      }
    }

    if (!$entity_type_options) {

      $entity_type_options = $this->io()->choice(dt("Choose an entity type to delete. All items of this"), $entities_info);
      // Return if no entity is chosen or entity invalid.
      if (!in_array($entity_type_options, array_keys($entities_info))) {
        return;
      }
    }

    $bundles_info = ['all' => 'All'];
    $bundle_definitions = $this->entityTypeBundleInfo->getBundleInfo($entity_type_options);

    if ($bundle_definitions) {
      foreach ($bundle_definitions as $id => $definition) {
        $bundles_info[$id] = $definition['label'];
      }

      // Check for presence of '--bundle' in drush command.
      if (drush_get_option('bundle')) {

        if ($vars && isset($vars[1])) {

          $bundle_type_options = $vars[1];

          if (!in_array($bundle_type_options, array_keys($bundles_info))) {
            throw new UserAbortException('Please select a valid bundle type');
            return;
          }

        }
      }

      if (!$bundle_type_options) {

        $bundle_type_options = $this->io()->choice(dt("Choose bundle type to delete. All items of this"), $bundles_info);

        if (!$bundle_type_options) {
          return;
        }
        // Delete all if bundle is All.
        if ($bundle_type_options == 'all') {
          $bundle_type_options = FALSE;
        }
      }
    }

    if ($this->io()->confirm('Are you sure you want to delete the entities?')) {

      // Get entities to delete.
      $entities_to_delete = $deleteEntity->getEntitiesToDelete($entity_type_options, $bundle_type_options, $entities_info_extended);

      if ($entity_type_options == 'user') {
        $entities_to_delete = array_diff($entities_to_delete, [0, 1]);
      }

      // Get batch array.
      $batch = $deleteEntity->getEntitiesDeleteBatch($entities_to_delete, $entity_type_options);
      // Initialize the batch.
      batch_set($batch);
      // Start the batch process.
      drush_backend_batch_process();

    }
    else {
      throw new UserAbortException();
    }
  }

}
