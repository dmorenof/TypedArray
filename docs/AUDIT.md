# Audit of TypedArray 1.0.2

Scope: `src/` at 1.0.2 (7 files, 333 lines), `composer.json`, README and examples. The audit ran the library against probes and then against a test suite written for it (24 tests).

## Findings

| # | Finding | Evidence | Resolution in 2.0.0 |
| --- | --- | --- | --- |
| 1 | `(string) $array` always crashed on PHP 8 | `trigger_error(…, E_NOTICE)` throws `ValueError`: PHP only accepts the `E_USER_*` levels | Raises the user warning "Array to string conversion" and returns the class name |
| 2 | `exchangeArray()` skipped validation | `(new IntegerArray([1]))->exchangeArray(['x'])` succeeded and the array held a string | Checks first; a failed exchange leaves the array unchanged |
| 3 | Interfaces could not be item types | The constructor used `class_exists()`, false for interfaces: `new Shapes([new Square])` threw "must be a valid primitive type or a class name" | Interfaces and enums are accepted |
| 4 | Only `gettype()` names were accepted | `int`, `float`, `bool`, `callable`, `iterable`, `mixed` were refused | Accepted, `gettype()` names still work |
| 5 | No union or nullable types | `int|string` and `?int` were refused | Supported, parsed once per declaration |
| 6 | No tests, CI, license file, or static analysis | None in the repository; `composer.json` declares MIT with no `LICENSE` | Added: 24 tests, PHPStan max, CI for PHP 8.1–8.4, MIT `LICENSE` |
| 7 | Junk type names | `unknown type` and `resource (closed)` were "valid" expected types | Removed (a closed resource still counts as a resource) |
| 8 | Messages used `gettype()` names | "integer given", "NULL given" | PHP type names (`int`, `null`, the class name) |

Behavior that stays on purpose: no implicit conversion (an `int` is not a `float`), keyed and list usage both work, `jsonSerialize()` returns the stored array.

## Native array compatibility

PHP cannot extend the `array` type: `array` is not a class, so no subclass can be passed where `array` is declared. What can be done, and is done, is the closest equivalent: extend `ArrayObject`, which gives `count()`, `foreach`, `[]`, `isset`/`unset`, the spread operator, `json_encode()` and `(array)`, plus `toArray()` for functions that only accept an array. Code that takes the collection should declare `iterable` (or `\Traversable`), not `array`. A native typed `array` would need a change in the language itself.

## Several types at once

A declaration is a union: `int|string`, `User|null`, `?int`. Every member can be a built-in type, class, interface or enum. Not covered, because it is a different feature: a tuple or shape (a type for each position or key). That would be a new class, not a change to this one.

## Relation to the development version in sdk-runtime

`dmorenof/sdk-runtime` has its own `Dmorenof\SdkRuntime\TypedArray` that extends this library and adds what only an API SDK needs: per-item and per-collection schema rules (`ITEM_VALIDATION`, `COLLECTION_VALIDATION`), serialization by request direction, and JSON `__toString()`. Recommendation:

- **Do not merge it into the library.** The rules are JSON Schema concepts that depend on the runtime; putting them here would make a small, dependency-free package depend on the API stack.
- **Do not rename.** `typedarray/typedarray` is used by other projects, and the runtime's class keeps its own name. Release this as **2.0.0**.
- The runtime's `^1.0` constraint must become `^1.0 || ^2.0`. One runtime test relied on finding 2 (it made an invalid collection through `exchangeArray()`); it needs to expect the `TypeError` earlier.

I did not find a separate development branch or repository besides this one and the runtime's class; if there is another, point me to it.
