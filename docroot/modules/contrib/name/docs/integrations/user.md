# User integration

Name integrates with Drupal core User so a configured Name field can provide
the account display name.

This updates the account **Display Name** (for example `getDisplayName()` and
labels shown in UI), not the account login name (`name`).

## Requirements

- Name module.
- Drupal core User module.
- A Name field on the `user` bundle.

## What Name provides

Name hooks into the user naming flow and field config lifecycle:

- `hook_user_format_name_alter()` uses the computed `realname` when available.
- `hook_user_load()` builds and caches `realname` from the configured preferred
  Name field.
- `hook_user_save()` clears cached realname values for changed users.
- `hook_field_config_create()` and `hook_field_config_delete()` keep
  `name.settings:user_preferred` in sync for user Name fields.

Formatting uses the Name formatter and the field's `override_format` setting.

## Site builder usage

1. Add a Name field to users (`/admin/config/people/accounts/fields`).
2. In that field's settings, enable:
   **Use this field to override the user's login name?**
3. Choose the **User name override format to use**.
4. Save settings and clear caches if needed.

After this, user display names come from that Name field formatting.

## Behavior notes

- The login username remains unchanged (`$account->getAccountName()`).
- The display label comes from Name (`$account->getDisplayName()`).
- Anonymous users are not altered.

## See also

- [API Overview](../api/overview.md#integration-with-other-modules)
- [Formatter service](../services/formatter.md)
- [Token integration](./token.md)
