# Name Formatter Service

## Service identity

- Service ID: `name.formatter`
- Interface: `Drupal\name\Service\NameFormatterInterface`
- Class: `Drupal\name\Service\NameFormatterService`

Use this service to format one or more name value arrays for display.

## Quick use

```php-inline
$formatter = \Drupal::service('name.formatter');

$output = $formatter->format([
  'title' => 'Dr.',
  'given' => 'Jane',
  'family' => 'Smith',
], 'full');
```

## Common methods

### `format(array $components, $type = 'default', $langcode = NULL)`

Formats one name value using a named pattern (`full`, `formal`, `given`,
`family`, custom IDs, and so on).

### `formatList(array $items, $type = 'default', $list_type = 'default', $langcode = NULL)`

Formats multiple values as a natural-language list using list-format settings.

### `setSetting($key, $value)` / `getSetting($key)`

Reads or updates runtime formatter settings for separators and markup strategy.

## Dependency injection example

```php-inline
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\name\Service\NameFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class NamePreviewBuilder implements ContainerInjectionInterface {

  public function __construct(
    private readonly NameFormatterInterface $formatter,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('name.formatter'),
    );
  }
}
```

## Related docs

- [Parser Service](parser.md)
- [Services Overview](index.md)
- [Code Examples](../examples/index.md)
