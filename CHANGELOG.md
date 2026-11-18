# Changelog

All notable changes will be documented here. Version numbering follows [Semantic Versioning](https://semver.org/).

## Unreleased (`develop`)

### Added
- Union and nullable types in `$expected_type` (`int|string`, `User|null`, `?int`), the names `int`, `float`, `bool`, `callable`, `iterable`, `mixed`, `false` and `true`, `GenericArray`, `TypedArray::of()`, `accepts()`, `getExpectedType()` and `toArray()`. All additive: existing declarations behave as before. See `docs/types.md`.
- MIT license and contribution/security policies.
- PHPUnit regression suite with a PHP 8.0–8.5 CI matrix.
- PHPDoc generics for typed collections and initial PHPStan analysis.
- Coverage report generation, minimum coverage enforcement, static-analysis type inference checks, and Dependabot configuration.

### Fixed in development
- Type validation before `exchangeArray()` replaces items.
- Validation after native `ArrayObject` unserialization.
- Support for class/interface-based collections.
- Correct distinction between open and closed resources.
- String conversion no longer calls `trigger_error()` with an invalid error constant.

### Decided
- The package keeps the name `typedarray/typedarray`; the staged rename to `php-typed-array/php-typed-array` and its `replace` mapping were dropped. Packagist skipped the `develop` branch because it declared a replace on its own registered name.

### Known limitations
- Native SPL iterator writes and `foreach` by reference may modify `ArrayObject` storage without invoking the explicit validators. This behavior is intentionally preserved in the compatible API, tested and documented.
- No stable release of the changes above has been published.

## 1.0.2 — 2024-12-28

Existing stable release. Historical changes predate this curated changelog; see Git history and tags for the complete record.
