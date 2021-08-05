<?php
/**
 * @file
 * Hooks into layout builder rendering to add our required attributes.
 */

namespace Drupal\facets\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\layout_builder\LayoutBuilderEvents;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;

/**
 * Class SectionComponentRender.
 */
class SectionComponentRender implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY] = ['onBuildRender'];

    return $events;
  }

  /**
   * Adds block classes to section component.
   *
   * @param \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent $event
   *   The section component render event.
   */
  public function onBuildRender(SectionComponentBuildRenderArrayEvent $event) {
    $build = $event->getBuild();
    if (!empty($build) && $build['#base_plugin_id'] === 'facet_block') {
      $attributes = isset($build['#attributes']) ? $build['#attributes'] : [];
      if (empty($build['content']['#attributes'])) {
        $build['content']['#attributes'] = [];
      }
      $build['#attributes'] = array_merge($build['content']['#attributes'], $attributes);
      $event->setBuild($build);
    }
  }

}
