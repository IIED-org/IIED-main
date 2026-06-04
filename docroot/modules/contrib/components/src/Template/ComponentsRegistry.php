<?php

namespace Drupal\components\Template;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\File\Exception\NotRegularDirectoryException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Loads info about components defined in themes or modules.
 */
class ComponentsRegistry {

  use LoggerChannelTrait;

  /**
   * The component registry for every theme.
   *
   * @var array
   *   An array of component registries, keyed by the theme name.
   */
  protected array $registry = [];

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected ThemeManagerInterface $themeManager;

  /**
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * The theme extension list service.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected ThemeExtensionList $themeExtensionList;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a new ComponentsRegistry object.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list service.
   * @param \Drupal\Core\Extension\ThemeExtensionList $themeExtensionList
   *   The theme extension list service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend for storing the components registry info.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(
    ModuleExtensionList $moduleExtensionList,
    ThemeExtensionList $themeExtensionList,
    ModuleHandlerInterface $moduleHandler,
    ThemeManagerInterface $themeManager,
    CacheBackendInterface $cache,
    FileSystemInterface $fileSystem,
    ConfigFactoryInterface $configFactory,
  ) {
    $this->moduleExtensionList = $moduleExtensionList;
    $this->themeExtensionList = $themeExtensionList;
    $this->moduleHandler = $moduleHandler;
    $this->themeManager = $themeManager;
    $this->cache = $cache;
    $this->fileSystem = $fileSystem;
    $this->configFactory = $configFactory;
  }

  /**
   * Gets the path to the given template.
   *
   * @param string $name
   *   The name of the template.
   *
   * @return null|string
   *   The path to the template, or NULL if not found.
   */
  public function getTemplate(string $name): ?string {
    if (!self::isValidComponentName($name)) {
      return NULL;
    }

    $themeName = $this->themeManager->getActiveTheme()->getName();
    if (!isset($this->registry[$themeName])) {
      $this->load($themeName);
    }

    if (!empty($this->registry[$themeName][$name])) {
      return $this->registry[$themeName][$name];
    }

    // If the template was not found in the active theme, and the active theme
    // is not the same as the default theme, load the default theme and check
    // there too. We may be dealing with the admin theme.
    $defaultThemeName = $this->config('system.theme')->get('default');
    if (empty($defaultThemeName) || $defaultThemeName === $themeName) {
      return NULL;
    }

    if (!isset($this->registry[$defaultThemeName])) {
      $this->load($defaultThemeName);
    }

    return $this->registry[$defaultThemeName][$name] ?? NULL;
  }

  /**
   * Validates if the given name is a valid component template name.
   *
   * @param string $name
   *   The name of the component template.
   */
  public static function isValidComponentName(string $name): bool {
    $extension = substr($name, strrpos($name, '.', -1));

    return ($extension === '.twig' || $extension === '.html' || $extension === '.svg')
      && $name[0] === '@'
      && $name[1] !== '/' && str_contains(substr($name, 2), '/');
  }

  /**
   * Retrieves a configuration object.
   *
   * @param string $name
   *   The name of the configuration object to retrieve.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   A configuration object.
   */
  protected function config(string $name): ImmutableConfig {
    return $this->configFactory->get($name);
  }

  /**
   * Ensures the component registry is available for the given active theme.
   *
   * @param string $themeName
   *   The name of the active theme.
   */
  protected function load(string $themeName): void {
    // Load from cache.
    if ($cache = $this->cache->get('components:registry:' . $themeName)) {
      $this->registry[$themeName] = $cache->data;
    }
    else {
      // Build the registry.
      $this->registry[$themeName] = [];

      // Get the full list of namespaces and their paths.
      $nameSpaces = $this->getNamespaces($themeName);

      $regex = '/\.(twig|html|svg)$/';

      foreach ($nameSpaces as $nameSpace => $nameSpacePaths) {
        foreach ($nameSpacePaths as $nameSpacePath) {
          try {
            // Get a listing of all Twig files in the namespace path.
            $files = $this->fileSystem->scanDirectory($nameSpacePath, $regex);
          }
          catch (NotRegularDirectoryException) {
            $this->logWarning(sprintf('The "@%s" namespace contains a path, "%s", that is not a directory.',
              $nameSpace,
              $nameSpacePath,
            ));
            $files = [];
          }
          ksort($files);
          foreach ($files as $filePath => $file) {
            // Register the full path and short path to the template.
            $templates = [
              '@' . $nameSpace . '/' . str_replace(rtrim($nameSpacePath, '/') . '/', '', $filePath),
              '@' . $nameSpace . '/' . $file->filename,
            ];
            foreach ($templates as $template) {
              if (!isset($this->registry[$themeName][$template])) {
                $this->registry[$themeName][$template] = $filePath;
              }
            }
          }
        }
      }

      // Only persist if all modules are loaded to ensure the cache is complete.
      if ($this->moduleHandler->isLoaded()) {
        $this->cache->set(
          'components:registry:' . $themeName,
          $this->registry[$themeName],
          Cache::PERMANENT,
          ['components_registry']
        );
      }
    }
  }

  /**
   * Get namespaces for the given theme.
   *
   * @param string $themeName
   *   The machine name of the theme.
   *
   * @return array
   *   The array of namespaces.
   */
  public function getNamespaces(string $themeName): array {
    if ($cached = $this->cache->get('components:namespaces:' . $themeName)) {
      return $cached->data;
    }

    // Load and cache un-altered Twig namespaces for all themes.
    if ($cached = $this->cache->get('components:namespaces')) {
      $allNamespaces = $cached->data;
    }

    if (!isset($allNamespaces) || !isset($allNamespaces[$themeName])) {
      $allNamespaces = $this->findNamespaces($this->moduleExtensionList, $this->themeExtensionList);
      // Only persist if all modules are loaded to ensure the cache is complete.
      if ($this->moduleHandler->isLoaded()) {
        $this->cache->set(
          'components:namespaces',
          $allNamespaces,
          Cache::PERMANENT,
          ['components_registry']
        );
      }
    }

    // Get the un-altered namespaces for the theme.
    $namespaces = $allNamespaces[$themeName]
      // If ::getNamespaces is called with a theme unknown to
      // ThemeExtensionList, we just return all the module namespaces.
      ?? $allNamespaces['$moduleNamespaces'];

    // Run hook_components_namespaces_alter().
    $this->moduleHandler->alter('components_namespaces', $namespaces, $themeName);
    $this->themeManager->alter('components_namespaces', $namespaces, $themeName);

    // Only persist if all modules are loaded to ensure the cache is complete.
    if ($this->moduleHandler->isLoaded()) {
      $this->cache->set(
        'components:namespaces:' . $themeName,
        $namespaces,
        Cache::PERMANENT,
        ['components_registry']
      );
    }

    return $namespaces;
  }

  /**
   * Finds namespaces for all installed themes.
   *
   * Templates in namespaces will be loaded from paths in this priority:
   * 1. active theme
   * 2. active theme's base themes
   * 3. modules:
   *    a. non-default namespaces
   *    b. default namespaces.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list service.
   * @param \Drupal\Core\Extension\ThemeExtensionList $themeExtensionList
   *   The theme extension list service.
   *
   * @return array
   *   An array of namespaces lists, keyed for each installed theme.
   */
  protected function findNamespaces(ModuleExtensionList $moduleExtensionList, ThemeExtensionList $themeExtensionList): array {
    $moduleInfo = $this->normalizeExtensionListInfo($moduleExtensionList);
    $themeInfo = $this->normalizeExtensionListInfo($themeExtensionList);

    $protectedNamespaces = $this->findProtectedNamespaces($moduleInfo + $themeInfo);

    // Collect module namespaces since they are valid for any active theme.
    $moduleNamespaces = [];

    // Find default namespaces for modules.
    foreach ($moduleInfo as $defaultName => &$info) {
      if (isset($info['namespaces'][$defaultName])) {
        $moduleNamespaces[$defaultName] = $info['namespaces'][$defaultName];
        unset($info['namespaces'][$defaultName]);
      }
    }

    // Find other namespaces defined by modules.
    foreach ($moduleInfo as &$info) {
      foreach ($info['namespaces'] as $namespace => $paths) {
        // Skip protected namespaces and log a warning.
        if (isset($protectedNamespaces[$namespace])) {
          $extensionInfo = $protectedNamespaces[$namespace];
          $this->logWarning(sprintf('The %s module attempted to alter the protected Twig namespace, %s, owned by the %s %s. See https://www.drupal.org/node/3190969#s-extending-a-default-twig-namespace to fix this error.', $info['extensionInfo']['name'], $namespace, $extensionInfo['name'], $extensionInfo['type']));
        }
        else {
          $moduleNamespaces[$namespace] = !isset($moduleNamespaces[$namespace])
            ? $paths
            : array_merge($paths, $moduleNamespaces[$namespace]);
        }
      }
    }

    // Remove protected namespaces from each theme's namespaces and log a
    // warning.
    foreach ($themeInfo as &$info) {
      foreach (array_keys($info['namespaces']) as $namespace) {
        if (isset($protectedNamespaces[$namespace])) {
          unset($info['namespaces'][$namespace]);
          $extensionInfo = $protectedNamespaces[$namespace];
          $this->logWarning(sprintf('The %s theme attempted to alter the protected Twig namespace, %s, owned by the %s %s. See https://www.drupal.org/node/3190969#s-extending-a-default-twig-namespace to fix this error.', $info['extensionInfo']['name'], $namespace, $extensionInfo['name'], $extensionInfo['type']));
        }
      }
    }

    // Build the full list of namespaces for each theme.
    $namespaces = [
      // Pass the module namespaces back to ::getNamespaces().
      '$moduleNamespaces' => $moduleNamespaces,
    ];
    foreach (array_keys($themeInfo) as $activeTheme) {
      // See the docs of this method as to why paths are prepended in this way.
      $namespaces[$activeTheme] = $moduleNamespaces;
      foreach (array_merge($themeInfo[$activeTheme]['extensionInfo']['baseThemes'], [$activeTheme]) as $themeName) {
        foreach ($themeInfo[$themeName]['namespaces'] as $namespace => $paths) {
          $namespaces[$activeTheme][$namespace] = !isset($namespaces[$activeTheme][$namespace])
            ? $paths
            : array_merge($paths, $namespaces[$activeTheme][$namespace]);
        }
      }
    }

    return $namespaces;
  }

  /**
   * Gets info from the given extension list and normalizes components data.
   *
   * If a namespace's path starts with a "/", the path is relative to the root
   * Drupal installation path (i.e. the directory that contains Drupal's "core"
   * directory.) Otherwise, the path is relative to the extension's path.
   *
   * @param \Drupal\Core\Extension\ExtensionList $extensionList
   *   The extension list to search.
   *
   * @return array
   *   Components-related info for all extensions in the extension list.
   */
  protected function normalizeExtensionListInfo(ExtensionList $extensionList): array {
    $data = [];

    $themeExtensions = ($extensionList instanceof ThemeExtensionList) ? $extensionList->getList() : [];
    foreach ($extensionList->getAllInstalledInfo() as $name => $extensionInfo) {
      $data[$name] = [
        // Save information about the extension.
        'extensionInfo' => [
          'name' => $extensionInfo['name'],
          'type' => $extensionInfo['type'],
          'package' => $extensionInfo['package'] ?? '',
        ],
      ];
      if (!empty($themeExtensions)) {
        $data[$name]['extensionInfo']['baseThemes'] = [];
        foreach ($themeExtensions[$name]->base_themes ?? [] as $baseTheme => $baseThemeName) {
          // If NULL is given as the name of any base theme, then Drupal
          // encountered an error trying to find the base theme. If this happens
          // for an active theme, Drupal will throw a fatal error. But this may
          // happen for a non-active, installed theme and the components module
          // should simply ignore the missing base theme since the error won't
          // affect the active theme.
          if (!is_null($baseThemeName)) {
            $data[$name]['extensionInfo']['baseThemes'][] = $baseTheme;
          }
        }
      }

      $info = isset($extensionInfo['components']) && is_array($extensionInfo['components'])
        ? $extensionInfo['components']
        : [];

      // Normalize namespace data.
      $data[$name]['namespaces'] = [];
      if (isset($info['namespaces'])) {
        $extensionPath = $extensionList->getPath($name);
        foreach ($info['namespaces'] as $namespace => $paths) {
          // Allow paths to be an array or a string.
          if (!is_array($paths)) {
            $paths = [$paths];
          }

          // Add the full path to the namespace paths.
          foreach ($paths as $key => $path) {
            // Determine if the given path is relative to the Drupal root or to
            // the extension.
            if ($path[0] === '/') {
              // Just remove the starting "/" to make it relative to the Drupal
              // root.
              $paths[$key] = ltrim($path, '/');
            }
            else {
              // $extensionPath is relative to the Drupal root.
              $paths[$key] = $extensionPath . '/' . $path;
            }
          }

          $data[$name]['namespaces'][$namespace] = $paths;
        }
      }

      // Find default namespace flag.
      $data[$name]['allow_default_namespace_reuse'] = isset($info['allow_default_namespace_reuse']);
    }

    return $data;
  }

  /**
   * Finds protected namespaces.
   *
   * @param array $extensionInfo
   *   The array of extensions in the format returned by
   *   normalizeExtensionListInfo().
   *
   * @return array
   *   The array of protected namespaces.
   */
  protected function findProtectedNamespaces(array $extensionInfo): array {
    $protectedNamespaces = [];

    foreach ($extensionInfo as $defaultName => $info) {
      // The extension opted-in to having its default namespace be reusable.
      if ($info['allow_default_namespace_reuse']) {
        continue;
      }

      // The extension is defining its default namespace; other extensions are
      // allowed to add paths to it.
      if (!empty($info['namespaces'][$defaultName])) {
        continue;
      }

      // All other default namespaces are protected.
      $protectedNamespaces[$defaultName] = [
        'name' => $info['extensionInfo']['name'],
        'type' => $info['extensionInfo']['type'],
        'package' => $info['extensionInfo']['package'] ?? '',
      ];
    }

    // Run hook_protected_twig_namespaces_alter().
    $this->moduleHandler->alter('protected_twig_namespaces', $protectedNamespaces);
    $this->themeManager->alter('protected_twig_namespaces', $protectedNamespaces);

    return $protectedNamespaces;
  }

  /**
   * Logs exceptional occurrences that are not errors.
   *
   * Example: Use of deprecated APIs, poor use of an API, undesirable things
   * that are not necessarily wrong.
   *
   * @param string $message
   *   The warning to log.
   * @param array $context
   *   Any additional context to pass to the logger.
   *
   * @internal
   */
  protected function logWarning(string $message, array $context = []): void {
    $this->getLogger('components')->warning($message, $context);
  }

}
