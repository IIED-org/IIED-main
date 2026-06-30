---
name: code-review-checklist
description: Checklist for reviewing code changes in this Drupal module
disable-model-invocation: true
---
# Code Review Checklist

## Backwards Compatibility
- [ ] No public or protected class/method/property/constant removed or renamed
- [ ] No required parameter added to an existing method
- [ ] No parameter type narrowed or return type widened on existing API
- [ ] No new methods added to existing interfaces in minor releases
- [ ] If new API is needed in a minor release, it uses a concrete class,
      sub-interface, new service, or utility (not an existing interface)
- [ ] If deprecating: introduction uses `name:8.x-1.x`; removal target uses `name:2.0.0` (semver next major)
- [ ] No service ID removed or renamed in `name.services.yml`
- [ ] No config key or schema key removed or renamed
- [ ] New Twig variables have safe fallbacks

## Coding Standards
- [ ] No `phpcs:ignore`, `phpcs:disable`, or `@codingStandardsIgnore` annotations
- [ ] `declare(strict_types=1)` present in all PHP files
- [ ] Type hints on all method parameters and return types
- [ ] PHPDoc blocks complete (`@param`, `@return`, `@throws`, `@deprecated` as needed)
- [ ] Array `=>` operators aligned in multi-line declarations
- [ ] Comments do not exceed 80 characters

## Testing & Tooling
- [ ] Tests run via `ddev phpunit`, not bare binaries
- [ ] `ddev phpcs` passes with no violations
- [ ] `ddev phpstan` passes with no new errors
- [ ] New behaviour is covered by unit or kernel tests

## Drupal & Security
- [ ] User-facing strings use `t()` or `TranslatableMarkup`
- [ ] Input is sanitized before output (no raw user data in markup)
- [ ] No raw SQL — Drupal database API used throughout
- [ ] Services use dependency injection, not `\Drupal::` static calls
