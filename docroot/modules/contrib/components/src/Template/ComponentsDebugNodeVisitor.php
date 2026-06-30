<?php

namespace Drupal\components\Template;

use Twig\Environment;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * A Twig node visitor that adds debug information around components.
 */
class ComponentsDebugNodeVisitor implements NodeVisitorInterface {

  /**
   * The components registry service.
   *
   * @var \Drupal\components\Template\ComponentsRegistry
   */
  protected ComponentsRegistry $componentsRegistry;

  /**
   * Constructs a new ComponentsDebugNodeVisitor object.
   *
   * @param \Drupal\components\Template\ComponentsRegistry $componentsRegistry
   *   The components registry service.
   */
  public function __construct(
    ComponentsRegistry $componentsRegistry,
  ) {
    $this->componentsRegistry = $componentsRegistry;
  }

  /**
   * {@inheritdoc}
   */
  public function enterNode(Node $node, Environment $env): Node {
    if (!$env->isDebug()) {
      return $node;
    }

    try {
      $name = $node->getSourceContext()->getName();
      $path = $this->componentsRegistry->getTemplate($name);

      if ($path && $node->hasNode('display_start') && $node->hasNode('display_end')) {
        $startText  = "<!-- THEME DEBUG -->\n";
        $startText .= "<!-- COMPONENT: {$name} -->\n";
        $startText .= "<!-- 💡 BEGIN ⚙️ COMPONENT TEMPLATE OUTPUT from '{$path}' -->\n";
        $endText    = "<!-- END ⚙️ COMPONENT TEMPLATE OUTPUT from '{$path}' -->\n";

        $node->getNode('display_start')
          ->setNode(
            '_components_debug_start',
            new Node([
              new TextNode($startText, 0),
            ])
          );
        $node->getNode('display_end')
          ->setNode(
            '_components_debug_end',
            new Node([
              new TextNode($endText, 0),
            ])
          );
      }
    }
    catch (\Exception) {
      // Ignore exception and return $node below.
    }

    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function leaveNode(Node $node, Environment $env): ?Node {
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority(): int {
    return 0;
  }

}
