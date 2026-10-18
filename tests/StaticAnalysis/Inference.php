<?php

declare(strict_types=1);

namespace TypedArray\StaticAnalysis;

use TypedArray\IntegerArray;
use TypedArray\StringArray;
use TypedArray\TypedArray;
use function PHPStan\Testing\assertType;

class Customer {}
class VipCustomer extends Customer {}

/** @extends TypedArray<Customer> */
final class CustomerArray extends TypedArray
{
    protected string $expected_type = Customer::class;
}

$integers = new IntegerArray([1, 2, 3]);
assertType('int', $integers[0]);

$strings = new StringArray(['a', 'b']);
assertType('string', $strings[0]);

$customers = new CustomerArray([new Customer(), new VipCustomer()]);
assertType(Customer::class, $customers[0]);
