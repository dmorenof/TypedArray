# Compatibility and runtime validation contract

TypedArray requires PHP 8.0 or later. CI exercises PHP 8.0 through PHP 8.5, including versions that no longer receive upstream security updates. Production deployments should use a supported PHP version.

## Validated operations

The constructor, `append()`, `offsetSet()`, and `exchangeArray()` validate each value before adding it to the native `ArrayObject` storage. Native unserialization validates restored elements and rejects changes to the declared expected type.

For these operations, incompatible values throw `TypeError`. The library uses PHP runtime types for integers, floats, booleans, strings, arrays, objects, and resources. Collections declared for a class or interface also accept compatible subclasses and implementations. PHPDoc annotations assist static analysis but do not affect runtime behavior.

## Native SPL writes are intentionally allowed

TypedArray extends `ArrayObject` for compatibility with native SPL collections. Some writes act directly on inherited native storage instead of invoking `TypedArray::offsetSet()` or `TypedArray::append()`:

- `$collection->getIterator()[0] = $value` (writable iterator returned by `getIterator()`).
- `(new \\ArrayIterator($collection))[0] = $value` (external writable iterator).
- `foreach ($collection as &$value) { ... }` (assignment through a reference).

These operations can change an element to a different type without throwing `TypeError`. They are deliberate native SPL interoperability behavior for the compatible 1.x API, not failed expectations in the test suite. The three paths are covered by executable tests in `tests/KnownLimitationsTest.php`.

For example:

```php
$numbers = new \\TypedArray\\IntegerArray([1, 2]);

foreach ($numbers as &$number) {
    $number = 'changed';
}
unset($number); // Release the last element's reference.

$numbers[0]; // 'changed' — assignment by reference did not run the validator.
$numbers[0] = 3; // Validated by TypedArray::offsetSet().
```

**Contract:** TypedArray validates its own construction, restoration, and explicit write methods. It does not promise that every value in native `ArrayObject` storage always matches the declared type after SPL write-through or by-reference mutation. Reading or iterating does not automatically revalidate existing values. Developers using these low-level mutations are responsible for the resulting contents.

Do not use TypedArray as a security boundary for untrusted or hostile code.

## Future strict invariants

A stronger invariant for all native mutation paths would require a separate design and a compatibility review. In particular, composition rather than `ArrayObject` inheritance could change `instanceof ArrayObject`, inherited methods, and iterator write-through semantics. Such a change belongs in a major release rather than being silently introduced into the compatible 1.x API.

## Backward compatibility

Published Composer users currently depend on `typedarray/typedarray` (stable tag `1.0.2`). Breaking changes require a new major version and migration guide. Development commits must not silently change existing stable releases.
