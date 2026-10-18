# Changelog

All notable changes will be documented here. Version numbering follows [Semantic Versioning](https://semver.org/).

## Unreleased (`develop`)

### Added
- MIT license and contribution/security policies.
- PHPUnit regression suite with a PHP 8.0–8.5 CI matrix.
- PHPDoc generics for typed collections and initial PHPStan analysis.
- Coverage report generation and Dependabot configuration.

### Fixed in development
- Type validation before `exchangeArray()` replaces items.
- Validation after native `ArrayObject` unserialization.
- Support for class/interface-based collections.
- Correct distinction between open and closed resources.
- String conversion no longer calls `trigger_error()` with an invalid error constant.

### Known limitations
- External `ArrayIterator` wrappers can mutate native `ArrayObject` storage without invoking validation. This requires an API-level redesign to eliminate completely.
- No stable release of the changes above has been published.

## 1.0.2 — 2024-12-28

Existing stable release. Historical changes predate this curated changelog; see Git history and tags for the complete record.
