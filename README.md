# Simple Typed Array

Simple abstract class for typed Arrays with a set of classes for the different basic types.

## Installation

### Composer

To install the library, execute the following code:

    composer require typedarray/typedarray

### Manual

Download the library from https://github.com/dmorenof/TypedArray

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

You can create your own typed array by extending the `TypedArray` class. For example:

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

class ClassTypeArray extends TypedArray {
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