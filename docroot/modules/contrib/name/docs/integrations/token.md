# Token integration

Drupal **core** replaces tokens for fields on entities (for example in text, Path, or custom code using `\Drupal::token()`). The bare field token uses Name’s **formatter** so output matches configured display formats. The [Token](https://www.drupal.org/project/token) contrib module adds a browsable token tree and related UI; it is **not** required for replacement to work.

Per-component tokens and the bare field token come from field token handling plus the name field formatter. Name adds **formatted** output via `hook_token_info_alter()` (hierarchical Token browser), `hook_tokens()` for chained `formatted_{field}:{format}` tokens, and `hook_tokens_alter()` for legacy `field:formatted:{format}` (and multi-value) strings so contrib Token does not clobber replacements.

## Requirements

- **Core only:** Name and the entity or field setup you are tokenizing (e.g. Node).
- **Token module (optional):** Install [Token](https://www.drupal.org/project/token) if you want the token browser and related helpers. Name lists `token:token` under `test_dependencies` in its info file, not as a runtime dependency.

## Example tokens

Patterns below follow `entity_type`, field machine name, optional delta, and property. Adjust `field_name`, bundle, and machine names to match your site. Examples mirror functional coverage in
`Drupal\Tests\name\Functional\NameNodeTokenReplaceTest` (node and user name fields).

| Token | Typical output |
| ----- | -------------- |
| `[node:field_name]` | Full name via formatter (all components the field uses) |
| `[node:field_name:title]` | Title component |
| `[node:field_name:given]` | Given name |
| `[node:field_name:middle]` | Middle name(s) |
| `[node:field_name:family]` | Family name |
| `[node:field_name:generational]` | Generational suffix |
| `[node:field_name:credentials]` | Credentials |
| `[node:field_name:formatted:FORMAT_ID]` | Full name text using the name format with machine name `FORMAT_ID` (see *Administration » Configuration » Regional and language » Name formats*) |
| `[node:formatted_field_name:FORMAT_ID]` | Same output as the row above; **Token** browser groups formats under one expandable “Formatted: …” entry (first value only) |
| `[node:field_name:DELTA:formatted:FORMAT_ID]` | Same as above for a multi-value field item at `DELTA` (use this when delta is not `0`; the `formatted_`… browser form is first value only) |

If `FORMAT_ID` is missing or not a valid name format, output uses the **default** name format (same as `name.formatter`). With the [Token](https://www.drupal.org/project/token) module, **Formatted** should appear under each name field next to **Given**, **Family**, and the other parts, with concrete formats (Default, Full, …) one level below that. Name’s `hook_token_info_alter()` runs **after** Token’s so it can attach to that subtree; if you upgrade or clear caches and still see a lone **Formatted: …** at the top of the entity list, rebuild the token registry (`drush cr`) or check that Token is enabled. The top-level form is only a fallback when Token does not expose a field chain for that instance.

For author references, user field tokens work the same way, for example `[node:author:field_realname]` and `[node:author:field_realname:family]`, and explicit formats such as `[node:author:field_realname:formatted:full]`, alongside core user tokens such as `[node:author:display-name]`.

!!! note "Multiple values"

    Delta-specific tokens (e.g. a second value on a multi-value field) follow core field token rules; see Drupal core field token documentation.

## Site builder notes

- The **bare** field token (`[node:YOUR_FIELD]`) reflects formatter settings for that field instance.
- **Per-property** tokens return the raw component strings.
- Install the Token module when editors need to pick tokens from a UI instead of memorizing patterns.

## See also

- [Integration overview](../api/overview.md#integration-with-other-modules)
- [Formatter service](../services/formatter.md)
