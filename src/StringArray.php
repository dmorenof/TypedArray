<?php

namespace TypedArray;

/** @extends TypedArray<string> */
class StringArray extends TypedArray
{
    protected string $expected_type = 'string';
}