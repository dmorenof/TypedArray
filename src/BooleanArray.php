<?php

namespace TypedArray;

/** @extends TypedArray<bool> */
class BooleanArray extends TypedArray
{
    protected string $expected_type = 'boolean';
}