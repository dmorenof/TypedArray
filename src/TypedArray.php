<?php

namespace TypedArray;

use ArrayObject;
use JsonSerializable;
use TypeError;

/**
 * An abstract class representing a type-safe array of items with enforced validations.
 *
 * This class extends ArrayObject and ensures that all items in the array
 * comply with the expected type defined in the implementing class.
 */
abstract class TypedArray extends ArrayObject implements JsonSerializable
{
    private const DEFAULT_PHP_TYPES = [
        'boolean',
        'integer',
        'double',
        'string',
        'array',
        'object',
        'resource',
        'NULL',
        'unknown type',
        'resource (closed)',
    ];
    protected string $expected_type;

    /**
     * Constructor method for the class.
     *
     * @param array $items An optional array of items to initialize and validate.
     * @return void
     */
    public function __construct(array $items = [])
    {
        if (empty($this->expected_type)) {
            throw new TypeError('The expected type must be defined in the implementing class.');
        }

        if (!in_array($this->expected_type, self::DEFAULT_PHP_TYPES) && !class_exists($this->expected_type)) {
            throw new TypeError('The expected type must be a valid primitive type or a class name.');
        }

        foreach ($items as $item) {
            $this->validate($item);
        }

        parent::__construct($items);
    }

    /**
     * Validates the given value against the expected type.
     *
     * @param mixed $value The value to validate.
     * @return void
     * @throws TypeError If the value doesn't match the expected type.
     */
    protected function validate(mixed $value): void
    {
        $type = gettype($value);

        /**
         * Assuming that "resource" and "resource (closed)" are the same type if not specified otherwise
         */
        if ($type === 'resource (closed)' && $this->expected_type === 'resource (closed)') {
            $type = 'resource';
        }

        if ($this->isInvalidType($type, $value)) {
            $actual_type = $type === 'object' && $this->expected_type !== 'object' ? get_class($value) : $type;
            $this->throwTypeError($actual_type);
        }
    }

    /**
     * Checks whether the given type and value don't match the expected type.
     *
     * The method determines if the provided type is invalid based on the expected type.
     * It cross-verifies against default PHP types or checks if the value is an instance
     * of the expected class when dealing with objects.
     *
     * @param string $type The type of the value being checked.
     * @param mixed $value The value to be validated against the expected type.
     * @return bool Returns true if the type or value is invalid; otherwise, false.
     * @throws TypeError
     */
    private function isInvalidType(string $type, mixed $value): bool
    {
        // If expected a default type
        if (in_array($this->expected_type, self::DEFAULT_PHP_TYPES)) {
            return $type !== $this->expected_type;
        } else if ($type === 'object') {
            return !($value instanceof $this->expected_type);
        }

        return true;
    }

    /**
     * Throws a TypeError indicating that an array item doesn't match the expected type.
     *
     * Constructs an error message using the expected type and the actual type provided,
     * then throws a TypeError with the formatted message.
     *
     * @param string $actual_type The type of the item if caused the error.
     *
     * @return void
     * @throws TypeError
     */
    private function throwTypeError(string $actual_type): void
    {
        throw new TypeError(sprintf(
            'Incorrect array item. Must be of type %s, %s given.',
            $this->expected_type,
            $actual_type
        ));
    }

    /**
     * Append a value to the array.
     *
     * @param mixed $value The value to be validated and appended to the array.
     * @return void
     */
    public function append(mixed $value): void
    {
        $this->validate($value);
        parent::append($value);
    }

    /**
     * Sets the value at the specified offset in the array.
     *
     * @param mixed $key The key at which to set the value.
     * @param mixed $value The value to set in the array, which will be validated.
     * @return void
     * @throws TypeError If a key other than null is provided.
     */
    public function offsetSet(mixed $key, mixed $value): void
    {
        $this->validate($value);
        parent::offsetSet($key, $value);
    }

    /**
     * Prepares data for JSON serialization.
     *
     * Converts the current object into an array structure suitable for JSON encoding.
     *
     * @return array Returns an associative array representation of the object.
     */
    public function jsonSerialize(): array
    {
        return (array)$this;
    }

    /**
     * Converts the current object to its string representation.
     *
     * To simulate the default behavior of an Array, triggers a Notice error
     * and returns a string with the class name.
     *
     * @return string Returns the class name of the current object.
     */
    public function __toString(): string
    {
        trigger_error('Array to string conversion', E_NOTICE);
        return get_class($this);
    }
}