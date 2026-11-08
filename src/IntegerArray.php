<?php

namespace TypedArray;

/** @extends TypedArray<int> */
class IntegerArray extends TypedArray
{
    protected string $expected_type = 'integer';
}