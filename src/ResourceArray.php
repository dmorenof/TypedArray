<?php

namespace TypedArray;

/** @extends TypedArray<resource> */
class ResourceArray extends TypedArray
{
    protected string $expected_type = 'resource';
}
