# Compatibility and type-safety guarantees

TypedArray requires PHP 8.0 or later. CI exercises PHP 8.0 through PHP 8.5, including PHP versions that no longer receive upstream security updates. Production deployments should use a supported PHP version.

## Guarantees

The constructor, `append()`, `offsetSet()` and `exchangeArray()` validate values before adding them to native `ArrayObject` storage. Native unserialization now validates restored elements.

The library differentiates integers, floats, booleans, strings, arrays, objects, and resources using PHP runtime types. Class and interface collections accept compatible implementing or extending objects. PHPDoc annotations improve IDE/static analysis but do not change PHP runtime behavior.

## Important inherited limitation

Because `TypedArray` extends PHP's native `ArrayObject`, external writable SPL iterators can bypass subclass validation. For example:

```php
$items = new \TypedArray\IntegerArray([1, 2]);
$iterator = new \ArrayIterator($items);
$iterator[0] = 'wrong'; // Bypasses TypedArray::offsetSet().
```

The `getIterator()` method of `ArrayObject` also exposes a writable iterator. These are genuine gaps, tracked by skipped regression specifications in `tests/KnownLimitationsTest.php`. **Do not use this class as a security boundary against hostile code**.

Eliminating all native SPL backdoors may require changing the inheritance design to composition. Such a change must be reviewed as a major version because code may depend on `instanceof ArrayObject`, inherited methods, or iterator write-through behavior.

## Backward compatibility

Published Composer users currently depend on `typedarray/typedarray` (stable tag `1.0.2`). Breaking changes require a new major version and migration guide. Development commits must not silently change existing stable releases.
