# Name Field Developer Docs

This documentation is for developers integrating or extending the Drupal Name
module in custom code.

## What the module provides

The field stores structured name components:

- `title`
- `given`
- `middle`
- `family`
- `generational`
- `credentials`

It also provides:

- field type, widget, and formatter plugins
- a `#type => 'name'` form element
- service APIs for formatting, parsing, generation, and autocomplete
- integration plugins for modules like Token, Feeds, and Diff
- integration with Drupal core `inline_form_errors`

## Service-first quick start

For class-based code (controllers, plugins, services), use constructor
injection:

```php-inline
use Drupal\name\Service\NameFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ExampleController {

  public function __construct(
    private readonly NameFormatterInterface $nameFormatter,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('name.formatter'),
    );
  }
}
```

For one-off scripts, you can still fetch services directly:

```php-inline
$formatter = \Drupal::service('name.formatter');
$label = $formatter->format([
  'given' => 'Jane',
  'family' => 'Smith',
], 'full');
```

## Documentation map

- [API Overview](api/overview.md)
- [Services](services/index.md)
  - [Formatter](services/formatter.md)
  - [Parser](services/parser.md)
  - [Generator](services/generator.md)
  - [Autocomplete](services/autocomplete.md)
  - [Options Provider](services/options-provider.md)
- [Hooks](hooks/widget-layouts.md)
- [Classes and Interfaces](classes/index.md)
- [Code Examples](examples/index.md)
- [Extending](extending/index.md)
- Integrations:
  - [Devel](integrations/devel.md)
  - [User](integrations/user.md)
  - [Taxonomy](integrations/taxonomy.md)
  - [Views](integrations/views.md)
  - [Help](integrations/help.md)
  - [Token](integrations/token.md)
  - [Feeds](integrations/feeds.md)
  - [Diff](integrations/diff.md)
  - [Migrate](integrations/migrate.md)
  - [Inline Form Errors](integrations/inline-form-errors.md)

## Version requirements

- Drupal 10.3+ or Drupal 11+
- PHP 8.3+

## Project resources

- [Project page](https://www.drupal.org/project/name)
- [Issue queue](https://www.drupal.org/project/issues/name)
- [Source repository](https://git.drupalcode.org/project/name)
