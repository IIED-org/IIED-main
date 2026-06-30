# Taxonomy integration

The Name module can pull option values from Taxonomy vocabularies for the
`title` and `generational` components.

## Requirements

- Name module.
- Taxonomy module enabled when using vocabulary-backed options.

Taxonomy is optional for Name overall, but required for
`[vocabulary:machine_name]` option tags to resolve.

## What Name provides

Name supports vocabulary references in field settings using this pattern:

- `[vocabulary:your_vocabulary_machine_name]`

When Name resolves options for a component:

- It detects vocabulary tags.
- It loads terms from the referenced vocabulary.
- It appends term labels that fit the component max length.
- It deduplicates options and can sort them if `sort_options` is enabled.

## Site builder usage

1. Go to Name field settings for your field.
2. In **Title options** or **Generational options**, add one or more lines like:
   `[vocabulary:titles]`
3. Save field settings.

The widget select list will include terms from those vocabularies.

!!! note "Validation behavior"

    If Taxonomy is disabled, or a referenced vocabulary cannot be found, Name
    validation reports a field settings error.

## Notes

- Vocabulary term labels longer than the component max length are skipped.
- Multiple vocabulary tags can be combined with manual options.
- Manual options may include `--` empty-option placeholders; see
  [Options Provider service](../services/options-provider.md#empty-option-placeholders)
  for the full convention and form-rendering pipeline.

## See also

- [API Overview](../api/overview.md#integration-with-other-modules)
- [Options Provider service](../services/options-provider.md)
- [Classes and Interfaces](../classes/index.md)
