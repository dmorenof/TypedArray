<?php

namespace TypedArray;

/**
 * A typed array whose type is given when it is created, for the cases where a subclass is not worth writing:
 *
 *   $ids = new GenericArray('int|string', [1, 'a']);
 *   $users = TypedArray::of(User::class, [$ada]);
 */
final class GenericArray extends TypedArray
{
    /** @param array<mixed> $items */
    public function __construct(string $type, array $items = [])
    {
        $this->expected_type = $type;
        parent::__construct($items);
    }
}
