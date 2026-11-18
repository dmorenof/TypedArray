<?php

namespace TypedArray;

/** @extends TypedArray<array<array-key, mixed>> */
class ArrayArray extends TypedArray
{
    protected string $expected_type = 'array';
}