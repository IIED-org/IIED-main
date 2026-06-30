# Feeds Integration

The [Feeds](https://www.drupal.org/project/feeds) integration maps source data
into Name field components.

## Provided target

- Class: `Drupal\name\Feeds\Target\NameTarget`
- Target ID: `name`

`NameTarget::prepareTarget()` exposes one property per core name component using
`Drupal\name\Utility\NameComponents::coreKeys()`:

- `title`
- `given`
- `middle`
- `family`
- `generational`
- `credentials`

## Mapper usage

In your Feed type mapper, map source columns/paths to only the components you
need. Unmapped components can remain empty.

## Related docs

- [API Overview](../api/overview.md)
- [Classes and Interfaces](../classes/index.md)
