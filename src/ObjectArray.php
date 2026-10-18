<?php

namespace TypedArray;

/** @extends TypedArray<object> */
class ObjectArray extends TypedArray
{
    protected string $expected_type = 'object';
}