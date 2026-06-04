# Name Autocomplete Service

## Service identity

- Service ID: `name.autocomplete`
- Interface: `Drupal\name\Service\AutocompleteInterface`
- Class: `Drupal\name\Service\AutocompleteService`

This service resolves autocomplete suggestions for name components based on
field configuration and available option sources.

## Quick use

```php-inline
$autocomplete = \Drupal::service('name.autocomplete');
$matches = $autocomplete->getMatches($fieldDefinition, 'given', 'Jan');
```

## Primary method

### `getMatches(FieldDefinitionInterface $field, $target, $string)`

- `$target` can be one component (`title`, `given`, `family`, and so on) or
  combinations such as `name`.
- Returns key/value suggestion pairs suitable for JSON autocomplete responses.
- Uses sources configured in field settings (options, stored values, taxonomy
  source values).

## Injection example

```php-inline
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\name\Service\AutocompleteInterface;

final class NameSuggester {

  public function __construct(
    private readonly AutocompleteInterface $autocomplete,
  ) {}

  public function suggest(FieldDefinitionInterface $field, string $input): array {
    return $this->autocomplete->getMatches($field, 'given', $input);
  }
}
```

## Related docs

- [Options Provider Service](options-provider.md)
- [Classes and Interfaces](../classes/index.md)
