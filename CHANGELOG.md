# Changelog

## [2.0.0]

### Added
- Union types in `$expected_type`: `int|string`, `User|null`, and `?int`.
- `callable`, `iterable`, `mixed`, `false`, `true` and the PHP names `int`, `float`, `bool` (the `gettype()` names still work).
- Interfaces are valid types (before, only classes and enums were).
- `GenericArray` and `TypedArray::of($type, $items)`: a typed array without writing a subclass.
- `accepts($value)`, `toArray()`, `getExpectedType()`.
- Tests, static analysis (PHPStan max) and CI for PHP 8.1–8.4.

### Fixed
- `exchangeArray()` accepted any value without checking it. It now checks first and leaves the array unchanged on failure. `unserialize()` also checks.
- `(string) $typedArray` always threw a `ValueError` on PHP 8 (`trigger_error()` refuses `E_NOTICE`). It now raises the "Array to string conversion" user warning and returns the class name.

### Changed (why this is a major release)
- Requires PHP 8.1 or later.
- The error message names the given type with PHP's names (`int`, `null`, the class) instead of `gettype()` names (`integer`, `NULL`).
- The unknown-type placeholders `unknown type` and `resource (closed)` are no longer accepted as expected types.
- The declared type is now validated on construction for every form above; an unknown name still throws a `TypeError`.

## [1.0.2] and earlier
See the Git history.
