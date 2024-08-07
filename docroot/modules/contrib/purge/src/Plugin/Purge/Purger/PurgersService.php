<?php

namespace Drupal\purge\Plugin\Purge\Purger;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\purge\Logger\LoggerServiceInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Processor\ProcessorInterface;
use Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException;
use Drupal\purge\Plugin\Purge\Purger\Exception\CapacityException;
use Drupal\purge\Plugin\Purge\Purger\Exception\DiagnosticsException;
use Drupal\purge\Plugin\Purge\Purger\Exception\LockException;
use Drupal\purge\ServiceBase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Provides the service that distributes access to one or more purgers.
 */
class PurgersService extends ServiceBase implements PurgersServiceInterface {

  /**
   * The name of the lock the purger service uses.
   *
   * @var string
   */
  const LOCKNAME = 'purge_purgers_service';

  /**
   * Purger logger format (sprintf), first %s is plugin_id, second instance ID.
   *
   * @var string
   */
  const LOGGER_PURGERS_FORMAT = 'purger_%s_%s';

  /**
   * The 'purge.purgers.tracker.capacity' service.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\CapacityTrackerInterface
   */
  protected $capacityTracker;

  /**
   * The factory for configuration objects.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Holds all generated user-readable purger labels per instance ID.
   *
   * @var null|string[]
   */
  protected $labels = NULL;

  /**
   * The lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * Logger channel specific to the purgers service.
   *
   * @var \Drupal\purge\Logger\LoggerChannelPartInterface
   */
  protected $logger;

  /**
   * Holds all loaded purgers plugins.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgerInterface[]
   */
  protected $purgers;

  /**
   * The 'purge.diagnostics' service.
   *
   * @var \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface
   */
  protected $purgeDiagnostics;

  /**
   * The 'purge.logger' service.
   *
   * @var \Drupal\purge\Logger\LoggerServiceInterface
   */
  protected $purgeLogger;

  /**
   * The 'purge.purgers.tracker.runtime_measurement' service.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\RuntimeMeasurementTrackerInterface
   */
  protected $runtimeMeasurementTracker;

  /**
   * The list of supported invalidation types across all purgers.
   *
   * @var null|string[]
   */
  protected $types = NULL;

  /**
   * The list of supported invalidation types per purger plugin.
   *
   * @var null|array[]
   */
  protected $typesByPurger = NULL;

  /**
   * Construct the purgers service.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   The plugin manager for this service.
   * @param \Drupal\purge\Logger\LoggerServiceInterface $purge_logger
   *   Logging services for the purge module and its submodules.
   * @param \Drupal\purge\Plugin\Purge\Purger\CapacityTrackerInterface $capacityTracker
   *   The capacity tracker.
   * @param \Drupal\purge\Plugin\Purge\Purger\RuntimeMeasurementTrackerInterface $runtimeMeasurementTracker
   *   The runtime measurement tracker.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface $purge_diagnostics
   *   The diagnostics service.
   */
  public function __construct(PluginManagerInterface $pluginManager, LoggerServiceInterface $purge_logger, CapacityTrackerInterface $capacityTracker, RuntimeMeasurementTrackerInterface $runtimeMeasurementTracker, ConfigFactoryInterface $config_factory, LockBackendInterface $lock, DiagnosticsServiceInterface $purge_diagnostics) {
    $this->pluginManager = $pluginManager;
    $this->purgeLogger = $purge_logger;
    $this->capacityTracker = $capacityTracker;
    $this->runtimeMeasurementTracker = $runtimeMeasurementTracker;
    $this->configFactory = $config_factory;
    $this->purgeDiagnostics = $purge_diagnostics;
    $this->lock = $lock;
    $this->logger = $this->purgeLogger->get('purgers');
  }

  /**
   * {@inheritdoc}
   */
  public function capacityTracker() {
    $this->initializePurgers();
    return $this->capacityTracker;
  }

  /**
   * Perform pre-flight checks.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[] $invalidations
   *   Non-associative array of invalidation objects that each describe what
   *   needs to be invalidated by the external caching system. Usually these
   *   objects originate from the queue but direct invalidation is also
   *   possible, in either cases the behavior of your plugin stays the same.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface::invalidate()
   *
   * @return bool
   *   TRUE if all checks passed, FALSE if there's reason to stop.
   */
  protected function checksBeforeTakeoff(array $invalidations) {

    // Block cache invalidation if there's a serious diagnostic severity.
    if ($fire = $this->purgeDiagnostics->isSystemOnFire()) {
      throw new DiagnosticsException($fire->getRecommendation());
    }

    // Stop when no invalidations are given (DX improvement) and then verify if
    // all incoming objects are InvalidationInterface compliant.
    if (empty($invalidations)) {
      $this->logger->debug("no invalidation objects given.");
      return FALSE;
    }
    foreach ($invalidations as $i => $invalidation) {
      if (!$invalidation instanceof InvalidationInterface) {
        throw new BadBehaviorException("Item $i is not a \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface derivative.");
      }
    }

    // Verify that we have the runtime capacity to process anything at all.
    $inv_limit = $this->capacityTracker()->getRemainingInvalidationsLimit();
    if (!$inv_limit) {
      $this->logger->debug("capacity limits exceeded.");
      throw new CapacityException('Capacity limits exceeded.');
    }
    if (($count = count($invalidations)) > $inv_limit) {
      $this->logger->debug(
        "capacity limit allows @limit invalidations during this request, @no given.",
        ['@limit' => $inv_limit, '@no' => $count]
      );
      throw new CapacityException("Capacity limit allows $inv_limit invalidations during this request, $count given.");
    }

    // Attempt to claim the lock to guard that we're the only one processing.
    $lease = $this->capacityTracker()->getLeaseTimeHint(count($invalidations));
    if (!$this->lock->acquire(self::LOCKNAME, (float) $lease)) {
      $this->logger->debug("could not acquire processing lock.");
      throw new LockException("Could not acquire processing lock.");
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function createId() {
    return substr(sha1(microtime()), 0, 10);
  }

  /**
   * {@inheritdoc}
   */
  public function getLabels() {
    if (is_null($this->labels)) {
      $this->initializePurgers();
      $this->labels = [];
      foreach ($this->getPluginsEnabled() as $id => $plugin_id) {
        $this->labels[$id] = $this->purgers[$id]->getLabel();
      }
    }
    return $this->labels;
  }

  /**
   * {@inheritdoc}
   *
   * @return string[]
   *   Associative array with enabled purgers: id => plugin_id.
   */
  public function getPluginsEnabled() {
    if (is_null($this->pluginsEnabled)) {
      $this->pluginsEnabled = [];
      $plugins = $this->configFactory->get('purge.plugins')->get('purgers');
      $plugin_ids = array_keys($this->getPlugins());

      // Put the plugin instances into $setting and use the order as key.
      if (!is_null($plugins)) {
        $setting = [];
        $plugins = array_filter($plugins);
        foreach ($plugins as $inst) {
          if (!in_array($inst['plugin_id'], $plugin_ids)) {
            // When a third-party provided purger was configured and its module
            // got uninstalled, the configuration renders invalid. Instead of
            // rewriting config or breaking hard, we ignore this silently.
            continue;
          }
          else {
            $setting[$inst['order_index']] = $inst;
          }
        }

        // Recreate the plugin ordering and propagate the enabled plugins array.
        ksort($setting);
        foreach ($setting as $inst) {
          $this->pluginsEnabled[$inst['instance_id']] = $inst['plugin_id'];
        }
      }
    }
    return $this->pluginsEnabled;
  }

  /**
   * {@inheritdoc}
   *
   * This method takes into account that purger plugins that are not
   * multi-instantiable, can only be loaded once and are no longer available if
   * they are already available. Plugins that are multi-instantiable, will
   * always be listed.
   */
  public function getPluginsAvailable() {
    $enabled = $this->getPluginsEnabled();
    $available = [];
    foreach ($this->getPlugins() as $plugin_id => $definition) {
      if ($definition['multi_instance']) {
        $available[] = $plugin_id;
      }
      else {
        if (!in_array($plugin_id, $enabled)) {
          $available[] = $plugin_id;
        }
      }
    }
    return $available;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypes() {
    if (is_null($this->types)) {
      $this->initializePurgers();
      $this->types = [];
      foreach ($this->purgers as $purger) {
        foreach ($purger->getTypes() as $type) {
          if (!in_array($type, $this->types)) {
            $this->types[] = $type;
          }
        }
      }
    }
    return $this->types;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypesByPurger() {
    if (is_null($this->typesByPurger)) {
      $this->initializePurgers();
      $this->typesByPurger = [];
      foreach ($this->getPluginsEnabled(FALSE) as $id => $plugin_id) {
        $this->typesByPurger[$id] = $this->purgers[$id]->getTypes();
      }
    }
    return $this->typesByPurger;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginsEnabled(array $plugin_ids) {
    $this->initializePurgers();

    // Validate that the given plugin_id's and instance ID's make sense.
    $definitions = $this->pluginManager->getDefinitions();
    foreach ($plugin_ids as $instance_id => $plugin_id) {
      if (!is_string($instance_id) || empty($instance_id)) {
        throw new \LogicException('Invalid instance ID (key).');
      }
      if (!isset($definitions[$plugin_id])) {
        throw new \LogicException('Invalid plugin_id.');
      }
    }

    // Find out which instances are being deleted and let those purgers cleanup.
    foreach ($this->getPluginsEnabled() as $instance_id => $plugin_id) {
      if (!isset($plugin_ids[$instance_id])) {
        $this->purgers[$instance_id]->delete();
        $this->purgeLogger->deleteChannel(sprintf(self::LOGGER_PURGERS_FORMAT, $plugin_id, $instance_id));
      }
    }

    // Write the new CMI setting and commit it.
    $order_index = 1;
    $setting = [];
    foreach ($plugin_ids as $instance_id => $plugin_id) {
      $order_index = $order_index + 1;
      $setting[] = [
        'order_index' => $order_index,
        'instance_id' => $instance_id,
        'plugin_id' => $plugin_id,
      ];
    }
    $this->configFactory
      ->getEditable('purge.plugins')
      ->set('purgers', $setting)
      ->save();

    // Make sure any new call to this service loads the new configuration.
    $this->reload();
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    parent::reload();
    // Without this, the tests will throw "failed to instantiate user-supplied
    // statement class: CREATE TABLE {cache_config}".
    // phpcs:ignore DrupalPractice.Objects.GlobalDrupal.GlobalDrupal
    $this->configFactory = \Drupal::configFactory();
    // Drush commands appreciate it when the config cache gets cleared.
    if (php_sapi_name() === 'cli') {
      // phpcs:ignore DrupalPractice.Objects.GlobalDrupal.GlobalDrupal
      \Drupal::cache('config')->deleteAll();
    }
    $this->purgers = NULL;
    $this->labels = NULL;
    $this->types = NULL;
    $this->typesByPurger = NULL;
  }

  /**
   * Propagate $this->purgers by initializing the purgers.
   */
  protected function initializePurgers() {
    if (!is_null($this->purgers)) {
      return;
    }

    // Iterate each purger plugin we should load and instantiate them.
    $this->purgers = [];
    foreach ($this->getPluginsEnabled() as $id => $plugin_id) {
      try {
        $this->purgers[$id] = $this->pluginManager->createInstance($plugin_id, ['id' => $id]);
        $this->purgers[$id]->setLogger($this->purgeLogger->get(sprintf(self::LOGGER_PURGERS_FORMAT, $plugin_id, $id)));
        $this->logger->debug("loading purger @id (@plugin_id).",
          ['@id' => $id, '@plugin_id' => $plugin_id]);
      }
      // When uninstalling modules providing purger plugins, instantiating
      // purgers may result in the attempt to instantiate services that are no
      // longer valid, in which case it is fine to just skip those.
      catch (ServiceNotFoundException $e) {
        $this->logger->error("Error loading purger @id (@plugin_id): @error",
          ['@id' => $id, '@plugin_id' => $plugin_id, '@error' => $e]);
      }
    }

    // Pass the purger instance onto depending objects.
    $this->capacityTracker->setPurgers($this->purgers);
    $this->runtimeMeasurementTracker->setPurgers($this->purgers);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate(ProcessorInterface $processor, array $invalidations) {
    $execution_time_start = microtime(TRUE);

    // Perform various (exception-throwing) pre-flight checks before we start.
    if (!$this->checksBeforeTakeoff($invalidations)) {
      return;
    }

    // If an exception is thrown in a plugin, we need to reliably release the
    // lock acquired in ::checksBeforeTakeoff().
    try {

      // Remove states from old purgers that got uninstalled.
      $purger_ids = array_keys($this->getPluginsEnabled());
      foreach ($invalidations as $invalidation) {
        foreach ($invalidation->getStateContexts() as $purger_id) {
          if (!in_array($purger_id, $purger_ids)) {
            $invalidation->removeStateContext($purger_id);
          }
        }
      }

      // Discover types in need of processing and, to be sure, reset state.
      $types = [];
      foreach ($invalidations as $i => $invalidation) {
        $types[$i] = $invalidation->getType();
        $invalidation->setStateContext(NULL);
      }

      // Iterate the purgers and start invalidating the items each one supports.
      $typesByPurger = $this->getTypesByPurger();
      foreach ($this->purgers as $id => $purger) {
        $groups = [];

        // Set context and presort the invalidations that this purger supports.
        foreach ($invalidations as $i => $invalidation) {
          $invalidation->setStateContext($id);
          if ($invalidation->getState() == InvalidationInterface::SUCCEEDED) {
            continue;
          }
          if (!in_array($types[$i], $typesByPurger[$id])) {
            $invalidation->setState(InvalidationInterface::NOT_SUPPORTED);
            continue;
          }
        }

        // Filter supported objects and group them by the right purger methods.
        foreach ($typesByPurger[$id] as $type) {
          $method = $purger->routeTypeToMethod($type);
          if (!isset($groups[$method])) {
            $groups[$method] = [];
          }
          foreach ($types as $i => $invalidation_type) {
            if ($invalidation_type === $type) {
              $groups[$method][$i] = $invalidations[$i];
            }
          }
        }

        // Invalidate objects by offering each group to method on the purger.
        foreach ($groups as $method => $offers) {
          if (!count($offers)) {
            continue;
          }

          // Leave a trace in the logs. when errors occur its likely after this.
          $this->logger->debug("offering @no items to @plugin's @id::@method()", [
            '@no' => count($offers),
            '@id' => $id,
            '@plugin' => $purger->getPluginId(),
            '@method' => $method,
          ]);

          // Feed the offers either with runtime measurement running or without.
          $has_runtime_measurement = $purger->hasRuntimeMeasurement();
          if ($has_runtime_measurement) {
            $instrument = $purger->getRuntimeMeasurement();
            $instrument->start();
            $purger->$method($offers);
            $instrument->stop($offers);
          }
          else {
            $purger->$method($offers);
          }
        }

        // Wait configured cooldown time before other purgers kick in.
        if (count($groups)) {
          $this->capacityTracker()->waitCooldownTime($id);
        }
      }

      // As processing finished we need to reset context. A call to getState()
      // will now lead to evaluation of the outcome for each object.
      foreach ($invalidations as $i => $invalidation) {
        $invalidation->setStateContext(NULL);
      }

      // Update the counters with runtime information and release the lock.
      $this->capacityTracker()->spentInvalidations()->increment(count($invalidations));
      $this->capacityTracker()->spentExecutionTime()->increment(microtime(TRUE) - $execution_time_start);
    }
    finally {
      $this->lock->release(self::LOCKNAME);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function movePurgerDown($purger_instance_id) {
    $enabled = $this->getPluginsEnabled();
    if (!isset($enabled[$purger_instance_id])) {
      throw new BadBehaviorException("Instance id '$purger_instance_id' is not enabled!");
    }

    // Build a numerically ordered copy of the enabled plugins array and put
    // only even numbers in. Then move $purger_instance_id in the odd spot down.
    $ordered = [];
    $index = 0;
    foreach ($enabled as $instance_id => $plugin_id) {
      $index = $index + 2;
      if ($instance_id === $purger_instance_id) {
        $ordered[$index + 3] = [$instance_id, $plugin_id];
      }
      else {
        $ordered[$index] = [$instance_id, $plugin_id];
      }
    }

    // Sort the array on key and rebuild the original array, reordered.
    ksort($ordered);
    $enabled = [];
    foreach ($ordered as $inst) {
      $enabled[$inst[0]] = $inst[1];
    }
    $this->setPluginsEnabled($enabled);
  }

  /**
   * {@inheritdoc}
   */
  public function movePurgerUp($purger_instance_id) {
    $enabled = $this->getPluginsEnabled();
    if (!isset($enabled[$purger_instance_id])) {
      throw new BadBehaviorException("Instance id '$purger_instance_id' is not enabled!");
    }

    // Build a numerically ordered copy of the enabled plugins array and put
    // only even numbers in. Then move $purger_instance_id in the odd spot up.
    $ordered = [];
    $index = 0;
    foreach ($enabled as $instance_id => $plugin_id) {
      $index = $index + 2;
      if ($instance_id === $purger_instance_id) {
        $ordered[$index - 3] = [$instance_id, $plugin_id];
      }
      else {
        $ordered[$index] = [$instance_id, $plugin_id];
      }
    }

    // Sort the array on key and rebuild the original array, reordered.
    ksort($ordered);
    $enabled = [];
    foreach ($ordered as $inst) {
      $enabled[$inst[0]] = $inst[1];
    }
    $this->setPluginsEnabled($enabled);
  }

}
