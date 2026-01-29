<?php

declare(strict_types=1);

namespace Drupal\matomo_tagmanager\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines the Matomo Tag Manager container configuration entity.
 *
 * @ConfigEntityType(
 *     id = "matomo_tagmanager_container",
 *     label = @Translation("Container"),
 *     label_singular = @Translation("container"),
 *     label_plural = @Translation("containers"),
 *     label_collection = @Translation("Containers"),
 *     handlers = {
 *         "storage" = "Drupal\matomo_tagmanager\ContainerStorage",
 *         "list_builder" = "Drupal\matomo_tagmanager\ContainerListBuilder",
 *         "form" = {
 *             "default" = "Drupal\matomo_tagmanager\Form\ContainerForm",
 *             "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *         }
 *     },
 *     admin_permission = "administer matomo tag manager",
 *     config_prefix = "container",
 *     entity_keys = {
 *         "id" = "id",
 *         "label" = "label",
 *         "weight" = "weight",
 *         "status" = "status"
 *     },
 *     config_export = {
 *         "id",
 *         "label",
 *         "weight",
 *         "container_url",
 *     },
 *     links = {
 *         "add-form" = "/admin/config/system/matomo-tagmanager/add",
 *         "edit-form" = "/admin/config/system/matomo-tagmanager/manage/{matomo_tagmanager_container}",
 *         "delete-form" = "/admin/config/system/matomo-tagmanager/manage/{matomo_tagmanager_container}/delete",
 *         "enable" = "/admin/config/system/matomo-tagmanager/manage/{matomo_tagmanager_container}/enable",
 *         "disable" = "/admin/config/system/matomo-tagmanager/manage/{matomo_tagmanager_container}/disable",
 *         "collection" = "/admin/config/system/matomo-tagmanager",
 *     }
 * )
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Container extends ConfigEntityBase implements ContainerInterface {

  use StringTranslationTrait;

  /**
   * The machine name for the configuration entity.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the configuration entity.
   *
   * @var string
   */
  public $label;

  /**
   * The weight of the configuration entity.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * The Matomo Tag Manager container URL.
   *
   * @var string
   */
  public $container_url = '';

  /**
   * {@inheritdoc}
   */
  public function containerUrl(): string {
    return $this->container_url;
  }

  /**
   * {@inheritdoc}
   */
  public function weight(): int {
    return $this->weight;
  }

  /**
   * Returns a cleansed variable.
   *
   * @param string $variable
   *   The variable name.
   *
   * @return string
   *   The cleansed variable.
   */
  public function variableClean($variable) {
    return \trim(\json_encode($this->get($variable)), '"');
  }

}
