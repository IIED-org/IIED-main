# Devel integration

Name supports development-time sample content generation through its field type
sample-value callback.

## Requirements

- Name module.
- Devel module only if you want Devel UI/workflows for generating content.

Name does not require Devel at runtime.

## What Name provides

The Name field type implements `generateSampleValue()` and returns a realistic
component array for one Name item.

Internally, this uses:

- Service ID: `name.generator`
- Method: `GeneratorInterface::generateSampleNames()`

Generated samples are cached in-process for reuse during a generation run.

## Developer and site builder usage

- Use Devel content-generation workflows to quickly create entities with Name
  field values.
- For custom scripts/tests, call `name.generator` directly when you need sample
  names without creating fixture data by hand.

## Notes

- Sample values follow Name component structure (`title`, `given`, `middle`,
  `family`, `generational`, `credentials`).
- Generated values are intended for testing/development, not canonical data.

## See also

- [Generator service](../services/generator.md)
- [API Overview](../api/overview.md#integration-with-other-modules)
