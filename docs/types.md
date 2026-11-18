# Declaring the accepted type

`$expected_type` is a string with one or more alternatives.

| Declaration | Accepts |
| --- | --- |
| `int`, `float`, `string`, `bool`, `array`, `object`, `resource`, `null`, `callable`, `iterable`, `mixed`, `false`, `true` | values of that type |
| `integer`, `double`, `boolean`, `NULL` | the same as `int`, `float`, `bool`, `null` (the names `gettype()` uses; kept from 1.0) |
| `resource (closed)` | closed resources only; `resource` accepts open ones |
| A class or interface name (`User::class`, `Countable`, an enum) | instances of it, including subclasses and implementations; a leading `\` is allowed |
| `int|string`, `User|null` | any member of the union |
| `?int` | the type or `null` |

Values are never converted: `1` is not accepted as a `float`. The declaration is checked when the array is created; an unknown name throws a `TypeError`. The same check runs on the constructor, `append()`, `[]`, `exchangeArray()` and unserialization, and `accepts($value)` tells whether a value would pass without throwing.

## Without a subclass

```php
$ids = new \TypedArray\GenericArray('int|string', [1, 'a']);
$users = \TypedArray\TypedArray::of(User::class, [$ada]);
```

## As a plain array

`toArray()` returns the items as a plain array, for the functions that only accept an array. PHP cannot extend the `array` type, so a parameter declared `array` does not accept a typed array; declare it `iterable` or `\Traversable`. See [compatibility](compatibility.md) for the native SPL behavior.

## What is not covered

A type per position or per key (a tuple or shape) is a different feature and is not part of this class.
