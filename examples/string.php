<?php

use TypedArray\StringArray;

require __DIR__ . '/../vendor/autoload.php';

try {
    // Example of usage without keys
    $array = new StringArray(['one', 'two', 'three']);
    echo json_encode($array, JSON_PRETTY_PRINT) . PHP_EOL;

    $array[] = 'four';
    echo json_encode($array, JSON_PRETTY_PRINT) . PHP_EOL;

    // Example of usage with keys
    $keyed_array = new StringArray(['foo' => 'hello', 'bar' => 'world']);
    echo json_encode($keyed_array, JSON_PRETTY_PRINT) . PHP_EOL;

    $keyed_array['baz'] = '!';
    echo json_encode($keyed_array, JSON_PRETTY_PRINT) . PHP_EOL;

    // Example of invalid value
    $invalid_array = new StringArray(['valid-string', 'another-string', 123]);
} catch (Error $e) {
    echo $e::class . ": " . $e->getMessage() . PHP_EOL;
}