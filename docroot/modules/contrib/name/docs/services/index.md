# Services Overview

Name module behavior is centered around container services. This is the
recommended API surface for custom code.

## Core services

| Service ID | Interface | Class |
|------------|-----------|-------|
| `name.formatter` | `Drupal\name\Service\NameFormatterInterface` | `Drupal\name\Service\NameFormatterService` |
| `name.format_parser` | `Drupal\name\Service\NameFormatParserInterface` | `Drupal\name\Service\NameFormatParserService` |
| `name.generator` | `Drupal\name\Service\GeneratorInterface` | `Drupal\name\Service\GeneratorService` |
| `name.autocomplete` | `Drupal\name\Service\AutocompleteInterface` | `Drupal\name\Service\AutocompleteService` |
| `name.options_provider` | `Drupal\name\Service\NameOptionInterface` | `Drupal\name\Service\NameOptionService` |

## Additional internal/public services

- `name.widget_layouts`
- `name.format_options`
- `name.additional_component`
- `name.user_realname_preload`
- `name.component_metadata`
- `name.element_validator`
- `name.twig.name_format_help` (`Drupal\name\Twig\NameFormatHelpTwigExtension`) —
  tagged Twig extension; registers `name_format_token_help()` for help topic
  templates. Not a general-purpose service for DI.

Use these when extending deeper module behavior (widget layouts, option maps,
component metadata, or validation flow).

## Dependency injection pattern

Prefer interface type hints and container injection:

```php-inline
use Drupal\name\Service\GeneratorInterface;

final class ExampleService {

  public function __construct(
    private readonly GeneratorInterface $generator,
  ) {}
}
```

If you need a quick ad hoc call:

```php-inline
$generator = \Drupal::service('name.generator');
$samples = $generator->generateSampleNames(3);
```

## Legacy compatibility note

Some legacy interface aliases and wrapper functions remain for backward
compatibility, but new development should target service interfaces and service
IDs listed above.
