# Simple Typed Array

Typed arrays for PHP: lists and maps that only accept one type, or a union of types such as `int|string`.

## Installation

### Composer

To install the library, execute the following code:

    composer require typedarray/typedarray

### Manual

Requires PHP 8.1 or later. Download the library from https://github.com/dmorenof/TypedArray

Include all the files from the "installation path/src"

    function autoload($path) {
        $items = glob($path . DIRECTORY_SEPARATOR . "*");
    
        foreach($items as $item) {
            $isPhp = pathinfo($item) ["extension"] === "php";

            if (is_file($item) && $isPhp) {
                require_once $item;
            } elseif (is_dir($item)) {
                autoload($item);
            }
        }
    }
    
    autoload($installation_path . DIRECTORY_SEPARATOR . 'src');

## Declaring a type

Extend `TypedArray\TypedArray` and set `$expected_type`, or create one without a subclass:

```php
use TypedArray\{TypedArray, IntegerArray};

class UserArray extends TypedArray { protected string $expected_type = User::class; }

$ids = new IntegerArray([1, 2]);                       // ready-made: Integer, String, Double, Boolean, Array, Object, Resource
$mixed = new TypedArray\GenericArray('int|string', [1, 'a']);
$users = TypedArray::of(User::class, [$ada]);          // same, as a factory
```

| Declaration | Accepts |
| --- | --- |
| `int`, `float`, `string`, `bool`, `array`, `object`, `resource`, `null`, `callable`, `iterable`, `mixed`, `false`, `true` | that type |
| `integer`, `double`, `boolean`, `NULL` | the same, with the names `gettype()` uses |
| `User::class`, an interface, an enum | instances of it (subclasses and implementations too) |
| `int\|string`, `User\|null` | any member of the union |
| `?int` | the type or `null` |

Values are never converted: `1` is not a `float`. Every way an item can enter is checked: the constructor, `append()`, `[]`, `exchangeArray()` and `unserialize()`. `accepts($value)` tells whether a value would pass without throwing.

## Using it like an array

PHP does not allow extending the `array` type, so a typed array extends `ArrayObject`: it can be counted, iterated, spread (`[...$a]`), read and written with `[]`, `isset`/`unset`, passed to `json_encode()` and cast with `(array)`. For functions that need a real array use `toArray()`:

```php
sort_by_name(...$users);          // spread works
array_map(fn ($u) => $u->name, $users->toArray());
```

A parameter declared `array` does not accept it; declare it `iterable` or `\Traversable` instead.

## Examples of Usage
Are you sick of typing your arrays with comments that are working in your IDEs, but your code still allows mixed values?

Here are some examples demonstrating how to use the library:

### Define a Custom Typed Array

Here is a simple solution to this problem.

Instead of typing your arrays like:

```php
/* @var int[] */
$my_int_array = [];
```

You can select a default typed array. For example:

```php
<?php
use TypedArray\IntegerArray;

$intArray = new IntegerArray();
$intArray[] = 10; // Valid
$intArray[] = 20; // Valid
// $intArray[] = "Hello"; // This will throw a TypeError
```

Default valid types are:
- TypedArray\ArrayArray 
- TypedArray\BooleanArray
- TypedArray\DoubleArray
- TypedArray\IntegerArray
- TypedArray\ObjectArray
- TypedArray\ResourceArray
- TypedArray\StringArray

## Working With Class Typed Arrays

You can also create a typed array that accepts only objects of a specific class type.

Extend from TypedArray\TypedArray and pass the class::name to the protected property expected type. For example:

```php
<?php
use TypedArray\TypedArray;
use Examples\MyCustomClass; // Assuming this class is defined elsewhere in your project

class MyCustomClassArray extends TypedArray {
    protected string $expected_type = MyCustomClass::class;
}

/* @var MyCustomClass[] */
$objectArray = new MyCustomClassArray();
$object1 = new MyCustomClass();
$object2 = new MyCustomClass();

$objectArray[] = $object1; // Valid
$objectArray[] = $object2; // Valid
// $objectArray[] = new SomeOtherClass(); // This will throw a TypeError
```

Also works with extended classes
```php
<?php

class User
{
    public string $name;
    public string $email;

    public function __construct(string $name, string $email)
    {
        $this->name = $name;
        $this->email = $email;
    }
}

class InvalidClass
{
}

class AdminUser extends User
{
}

class UserArray extends TypedArray
{
    protected string $expected_type = User::class;
}

// Example of usage without keys
$userArray = new UserArray([
    new User('John Doe', 'john@example.com'),
    new User('Jane Smith', 'jane@example.com'),
    new AdminUser('Admin User', 'admin@example.com'),
]);

$userArray[] = new User('Alice Johnson', 'alice@example.com');

// Example of usage with keys
$keyedUserArray = new UserArray([
    'admin' => new AdminUser('Admin User', 'admin@example.com'),
    'editor' => new User('Editor User', 'editor@example.com'),
]);

$keyedUserArray['viewer'] = new User('Viewer User', 'viewer@example.com');

// Example of invalid value
$invalidUserArray = new UserArray([
    'invalid-value', // This will throw a TypeError
    new InvalidClass(), // This will throw a TypeError 
]);
```

### Handle Exceptions

When a value doesn't meet the validation rules, an exception is thrown. You can handle it as follows:

```php
<?php
try {
    $intArray[] = "Invalid Value";
} catch (TypeError $TypeError) {
    echo "Caught exception: " . $TypeError->getMessage(); // Validation error message
}
```
