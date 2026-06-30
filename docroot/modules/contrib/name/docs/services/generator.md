# Name Generator Service

## Service identity

- Service ID: `name.generator`
- Interface: `Drupal\name\Service\GeneratorInterface`
- Class: `Drupal\name\Service\GeneratorService`

Use this service to generate realistic sample name values for tests, demos, and
seed data.

## Quick use

```php-inline
$generator = \Drupal::service('name.generator');

$samples = $generator->generateSampleNames(5);
```

## Common methods

### `generateSampleNames($limit = 3, ?FieldDefinitionInterface $field_definition = NULL)`

Returns `$limit` generated name arrays. If you pass a field definition, results
respect that field's component and length settings.

### `loadSampleValues($component, $gender = NULL)`

Returns source sample values for one component (`given`, `family`, `title`,
etc.), optionally filtered by gender where supported.

## Injection example

```php-inline
use Drupal\name\Service\GeneratorInterface;

final class DemoNameSeeder {

  public function __construct(
    private readonly GeneratorInterface $generator,
  ) {}

  public function buildNames(int $count): array {
    return $this->generator->generateSampleNames($count);
  }
}
```

## Related docs

- [Formatter Service](formatter.md)
- [Services Overview](index.md)
