<?php

namespace Drupal\components\Template\Loader;

use Drupal\components\Template\ComponentsRegistry;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;

// cspell:ignore mycomponents mythemeComponents
/**
 * Loads namespaced templates from the filesystem.
 *
 * This loader adds module and theme defined namespaces to the Twig filesystem
 * loader so that templates can be referenced by namespace, like
 * \@mycomponents/box.twig or \@mythemeComponents/page.twig.
 */
class ComponentsLoader extends FilesystemLoader {

  /**
   * The components registry service.
   *
   * @var \Drupal\components\Template\ComponentsRegistry
   */
  protected ComponentsRegistry $componentsRegistry;

  /**
   * Constructs a new ComponentsLoader object.
   *
   * @param \Drupal\components\Template\ComponentsRegistry $componentsRegistry
   *   The components registry service.
   */
  public function __construct(ComponentsRegistry $componentsRegistry) {
    parent::__construct();

    $this->componentsRegistry = $componentsRegistry;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Twig\Error\LoaderError
   *   Thrown if a template matching $name cannot be found and $throw is TRUE.
   */
  protected function findTemplate(string $name, bool $throw = TRUE): ?string {
    // componentsRegistry::getTemplate() returns a string or NULL, exactly
    // what componentsLoader::findTemplate() should return.
    $path = $this->componentsRegistry->getTemplate($name);

    if ($path || !$throw) {
      return $path;
    }

    throw new LoaderError(
      ComponentsRegistry::isValidComponentName($name)
      ? sprintf('Unable to find template "%s" in the components registry.', $name)
      : sprintf('Malformed namespaced template name "%s" (expecting "@namespace/template_name.twig").', $name)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function exists(string $name): bool {
    return (bool) $this->componentsRegistry->getTemplate($name);
  }

}
