<?php

namespace Drupal\ds\Plugin\DsField;

use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * The base plugin to create DS fields.
 */
abstract class Field extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    // Initialize output.
    $output = '';

    // Basic string.
    $entity_render_key = $this->entityRenderKey();

    if (isset($config['link text'])) {
      $output = $this->t($config['link text']);
    }
    elseif (!empty($entity_render_key) && isset($this->entity()->{$entity_render_key})) {
      if ($this->getEntityTypeId() == 'user' && $entity_render_key == 'name') {
        $output = $this->entity()->getAccountName();
      }
      else {
        $output = $this->entity()->{$entity_render_key}->value;
      }
    }

    if (empty($output)) {
      return [];
    }

    $template = <<<TWIG
{% if wrapper %}
<{{ wrapper }}{{ attributes }}>
{% endif %}
{% if is_link %}
  {{ link(output, entity_url, link_attributes) }}
{% else %}
  {{ output }}
{% endif %}
{% if wrapper %}
</{{ wrapper }}>
{% endif %}
TWIG;

    // Sometimes it can be impossible to make a link to the entity, because it
    // has no id as it has not yet been saved, e.g. when previewing an unsaved
    // inline entity form.
    $is_link = FALSE;
    $entity_url = NULL;
    if (!empty($this->entity()->id())) {
      $is_link = !empty($config['link']) || !empty($config['mail_link']);

      if (!empty($config['mail_link'])) {
        $entity_url = Url::fromUri('mailto:' . $output);
      }
      else {
        $entity_url = $this->entity()->toUrl();
      }
      if (!empty($config['link class'])) {
        $entity_url->setOption('attributes', ['class' => explode(' ', $config['link class'])]);
      }
    }

    // Build the attributes.
    $attributes = new Attribute();
    if (!empty($config['class'])) {
      $attributes->addClass($config['class']);
    }

    // Build the link attributes.
    $link_attributes = new Attribute();
    if (!empty($config['link'])) {
      if (!empty($config['link class'])) {
        $link_attributes->addClass($config['link class']);
      }
      if (!empty($config['link_target'])) {
        $link_attributes->setAttribute('target', $config['link_target']);
      }
    }

    return [
      '#type' => 'inline_template',
      '#template' => $template,
      '#context' => [
        'is_link' => $is_link,
        'wrapper' => !empty($config['wrapper']) ? $config['wrapper'] : '',
        'attributes' => $attributes,
        'link_attributes' => $link_attributes,
        'entity_url' => $entity_url,
        'output' => $output,
      ],
    ];
  }

  /**
   * Returns the entity render key for this field.
   */
  protected function entityRenderKey() {
    return '';
  }

}
