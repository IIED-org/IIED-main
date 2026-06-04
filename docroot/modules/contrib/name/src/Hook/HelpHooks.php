<?php

declare(strict_types=1);

namespace Drupal\name\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\field\FieldConfigInterface;

/**
 * Hook implementations for help pages.
 *
 * @internal
 */
final class HelpHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  // phpcs:ignore Drupal.Commenting.PostStatementComment.Found -- #[Hook] is a PHP attribute, not a trailing comment.
  #[Hook('help')]   // @phpstan-ignore attribute.notFound
  public function help(string $route_name, RouteMatchInterface $route_match): string {
    if ($this->isNameFieldConfigRoute($route_name, $route_match)) {
      return $this->fieldConfigHelp($route_match);
    }

    $output = '';
    switch ($route_name) {
      case 'help.page.name':
        $output = '<p><a href="https://git.drupalcode.org/project/name/-/commits/8.x-1.x"><img alt="coverage report" src="https://git.drupalcode.org/project/name/badges/8.x-1.x/coverage.svg" /></a> &nbsp;';
        $output .= '<a href="https://git.drupalcode.org/project/name/-/commits/8.x-1.x"><img alt="pipeline status" src="https://git.drupalcode.org/project/name/badges/8.x-1.x/pipeline.svg" /></a> </p>';
        $output .= '<p> <a href="https://www.drupal.org/project/name">Homepage</a> </p>';
        $output .= '<p> <a href="https://www.drupal.org/node/3559658">Documentation</a> </p>';
        $output .= '<p> <a href="https://project.pages.drupalcode.org/name/">Developer Documentation</a> </p>';
        $output .= '<p> <a href="https://www.drupal.org/project/issues/name?version=any_8.x-">Issues</a> </p>';
        $output .= '<p>' . $this->t("The Name field stores a person's name in parts. You can store title, given name, middle name, family name, generational suffix, and credentials.") . '</p>';
        $output .= '<p>' . $this->t('Use this field when you need better control than one plain text box. You can choose which parts are shown, required, and used for display.') . '</p>';
        $output .= '<h2>' . $this->t('Common tasks') . '</h2>';
        $output .= '<ul>';
        $output .= '<li>' . $this->t('Choose which name parts are enabled in field settings.') . '</li>';
        $output .= '<li>' . $this->t('Pick a format for how names are shown on pages.') . '</li>';
        $output .= '<li>' . $this->t('Adjust widget labels and layout for editors.') . '</li>';
        $output .= '<li>' . $this->t('Set list format rules for fields with many names.') . '</li>';
        $output .= '</ul>';
        $output .= '<h2>' . $this->t('Related help') . '</h2>';
        $output .= '<ul>';
        foreach ($this->relatedHelpTopics() as $topic => $label) {
          $output .= '<li>' . $this->helpTopicLink($topic, $label) . '</li>';
        }
        $output .= '</ul>';
        break;

      case 'name.settings':
        $output .= '<p>' . $this->t('Configure the separator replacement tokens used by Name formats.') . '</p>';
        $output .= '<p>' . $this->t('For guidance on separators and token usage, see') . ' ';
        $output .= $this->helpTopicLink('name.formats', $this->t('Name formats'));
        $output .= '.</p>';

        break;

      case 'name.name_format_list':
      case 'name.name_format_add':
      case 'entity.name_format.edit_form':
        $output .= '<p>' . $this->t('Name formats control how a single name is shown.') . '</p>';
        $output .= '<p>' . $this->t('For pattern examples, see') . ' ';
        $output .= $this->helpTopicLink('name.formats', $this->t('Name formats'));
        $output .= '.</p>';
        break;

      case 'name.name_list_format_list':
      case 'name.name_list_format_add':
      case 'entity.name_list_format.edit_form':
        $output .= '<p>' . $this->t('Name list formats control how multiple names are joined together.') . '</p>';
        $output .= '<p>' . $this->t('For list behavior and examples, see') . ' ';
        $output .= $this->helpTopicLink(
          'name.list_formats',
          $this->t('Name list formats'),
        );
        $output .= '.</p>';
        break;

      default:
        break;
    }

    return $output;
  }

  /**
   * Determines whether this is a Name field configuration route.
   *
   * @param string $route_name
   *   The route name.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return bool
   *   TRUE when the route configures a Name field.
   */
  private function isNameFieldConfigRoute(
    string $route_name,
    RouteMatchInterface $route_match,
  ): bool {
    if (!str_starts_with($route_name, 'entity.field_config.')) {
      return FALSE;
    }

    $field_config = $route_match->getParameter('field_config');
    return $field_config instanceof FieldConfigInterface
      && $field_config->getType() === 'name';
  }

  /**
   * Builds help for Name field configuration pages.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return string
   *   The rendered help text.
   */
  private function fieldConfigHelp(RouteMatchInterface $route_match): string {
    $field_config = $route_match->getParameter('field_config');
    assert($field_config instanceof FieldConfigInterface);

    $output = '<p>' . $this->t('Name field settings control which name parts are active and how editors enter data.') . '</p>';
    $output .= '<p>' . $this->t('For field options, see') . ' ';
    $output .= $this->helpTopicLink(
      'name.field_settings',
      $this->t('Field settings'),
    );
    $output .= '. ' . $this->t('For widget labels and layout, see') . ' ';
    $output .= $this->helpTopicLink(
      'name.widget_options',
      $this->t('Widget options'),
    );
    $output .= '.</p>';
    $output .= '<p>' . $this->t('For autocomplete sources and title values, see') . ' ';
    $output .= $this->helpTopicLink('name.autocomplete', $this->t('Autocomplete'));
    $output .= ' ' . $this->t('and') . ' ';
    $output .= $this->helpTopicLink('name.titles', $this->t('Titles'));
    $output .= '.</p>';

    if ($field_config->getTargetEntityTypeId() === 'user') {
      $output .= '<p>' . $this->t('For using a Name field as the shown user name, see') . ' ';
      $output .= $this->helpTopicLink(
        'name.user_display_name',
        $this->t('User display name'),
      );
      $output .= '.</p>';
    }

    return $output;
  }

  /**
   * Returns related help topics for the Name help page.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
   *   Help topic labels keyed by help topic ID.
   */
  private function relatedHelpTopics(): array {
    return [
      'name.components'        => $this->t('Name components'),
      'name.field_settings'    => $this->t('Field settings'),
      'name.formats'           => $this->t('Name formats'),
      'name.widget_options'    => $this->t('Widget options'),
      'name.formatter_options' => $this->t('Formatter options'),
      'name.list_formats'      => $this->t('Name list formats'),
    ];
  }

  /**
   * Builds a link to a Name help topic.
   *
   * @param string $topic
   *   The help topic ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The link label.
   *
   * @return string
   *   The rendered help topic link.
   */
  private function helpTopicLink(string $topic, TranslatableMarkup $label): string {
    return (string) Link::fromTextAndUrl(
      $label,
      Url::fromUri('internal:/admin/help/topic/' . $topic),
    )->toString();
  }

}
