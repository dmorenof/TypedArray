# Simple Typed Array

Simple abstract class for typed Arras.

## Installation

### Composer

To install the library, execute the following code:

    composer require typedarray/typedarray master

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

Here are some examples demonstrating how to use the library:

### Define a Custom Typed Array

You can create your own typed array by extending the `TypedArray` class. For example:

```php
<?php
use TypedArray\TypedArray;
class IntegerArray extends TypedArray {
    protected string $expected_type = 'integer';
}
$intArray = new IntegerArray();
$intArray[] = 10; // Valid
$intArray[] = 20; // Valid
// $intArray[] = "Hello"; // This will throw a type error
```

### Work With Typed Maps

You can also work with key-value pairs if you extend the library for typed maps:

```php
<?php
use TypedArray\TypedArray;
class StringMap extends TypedArray {
    protected string $expected_type = 'string';
}
$map = new StringToIntMap();
$map["key1"] = "Foo";
$map["key2"] = "Hello World";
// var_dump($map["key2"]); // Outputs "Hello World"
```

### Define a Typed Array of Class Type

You can also create a typed array that accepts only objects of a specific class type. For example:

```php
<?php
use TypedArray\TypedArray;
use Examples\MyCustomClass; // Assuming this class is defined elsewhere in your project

class ClassTypeArray extends TypedArray {
    protected string $expected_type = MyCustomClass::class;
}

$objectArray = new ClassTypeArray();

$object1 = new MyCustomClass();
$object2 = new MyCustomClass();

$objectArray[] = $object1; // Valid
$objectArray[] = $object2; // Valid
// $objectArray[] = new SomeOtherClass(); // This will throw a TypeError
```


### Handle Exceptions

When a value does not meet the validation rules, an exception is thrown. You can handle it as follows:

```php
<?php
try {
    $intArray[] = "Invalid Value";
} catch (TypeError $e) {
    echo "Caught exception: " . $e->getMessage(); // Validation error message
}
```