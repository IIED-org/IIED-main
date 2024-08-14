<?php

namespace Drupal\rabbit_hole;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates permission for each supported entity type.
 */
class RabbitHolePermissionGenerator implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity plugin manager.
   *
   * @var \Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginManager
   */
  protected $rhEntityPluginManager;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $etm, RabbitHoleEntityPluginManager $entity_plugin_manager, TranslationInterface $translation) {
    $this->entityTypeManager = $etm;
    $this->rhEntityPluginManager = $entity_plugin_manager;
    $this->stringTranslation = $translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.rabbit_hole_entity_plugin'),
      $container->get('string_translation')
    );
  }

  /**
   * Return an array of per-entity rabbit hole permissions.
   *
   * @return array
   *   An array of permissions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function permissions() {
    $permissions = [];

    foreach ($this->rhEntityPluginManager->getDefinitions() as $def) {
      $entity_type = $this->entityTypeManager
        ->getStorage($def['entityType'])
        ->getEntityType();
      $permissions += [
        'rabbit hole administer ' . $def['entityType'] => [
          'title' => $this->t('Administer Rabbit Hole settings for %entity_type', [
            '%entity_type' => $entity_type->getLabel(),
          ]),
        ],
        'rabbit hole bypass ' . $def['entityType'] => [
          'title' => $this->t('Bypass Rabbit Hole action for %entity_type', [
            '%entity_type' => $entity_type->getLabel(),
          ]),
        ],
      ];
    }

    return $permissions;
  }

}
