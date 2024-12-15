<?php

use TypedArray\BooleanArray;

require __DIR__ . '/../vendor/autoload.php';

try {
    // Example of usage without keys
    $array = new BooleanArray([true, false, true]);
    echo json_encode($array, JSON_PRETTY_PRINT) . PHP_EOL;

    $array[] = true;
    echo json_encode($array, JSON_PRETTY_PRINT) . PHP_EOL;

    // Example of usage with keys
    $keyed_array = new BooleanArray(['foo' => true, 'bar' => false]);
    echo json_encode($keyed_array, JSON_PRETTY_PRINT) . PHP_EOL;

    $keyed_array['baz'] = true;
    echo json_encode($keyed_array, JSON_PRETTY_PRINT) . PHP_EOL;

    // Example of invalid value
    $invalid_array = new BooleanArray([true, false, "not-a-boolean"]);
} catch (Error $e) {
    echo $e::class . ": " . $e->getMessage() . PHP_EOL;
}