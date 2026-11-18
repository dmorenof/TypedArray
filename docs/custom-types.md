# Typed object collections

The class-oriented use case is a typed list of SDK domain objects:

```php
use TypedArray\TypedArray;

class User
{
    public function __construct(public readonly int $id) {}
}

/** @extends TypedArray<User> */
final class UserArray extends TypedArray
{
    protected string $expected_type = User::class;
}

$users = new UserArray([new User(1)]);
$users[] = new User(2);
// $users[] = new stdClass(); // TypeError
```

The PHPDoc `@extends TypedArray<User>` annotation is for PhpStorm/PHPStan and other tools that understand generic templates; the `$expected_type` property enforces the type at runtime.

You can also use an interface:

```php
interface ResultItem {}
class SuccessItem implements ResultItem {}

/** @extends TypedArray<ResultItem> */
final class ResultArray extends TypedArray
{
    protected string $expected_type = ResultItem::class;
}

$results = new ResultArray([new SuccessItem()]);
```

Objects implementing that interface are accepted. Note that validation checks the element type, not the shape of the keys or the depth of nested arrays.

See [compatibility and limitations](compatibility.md) before relying on an ArrayObject-derived collection as a security boundary.
