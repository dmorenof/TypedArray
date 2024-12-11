<?php

namespace TypedArray;

use ArrayObject;
use InvalidArgumentException;
use JsonSerializable;

/**
 * An abstract class representing a type-safe array of items with enforced validations.
 *
 * This class extends ArrayObject and ensures that all items in the array
 * comply with the expected type defined in the implementing class.
 */
abstract class TypedArray extends ArrayObject implements JsonSerializable
{
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
            throw new InvalidArgumentException('The expected type must be defined in the implementing class.');
        }

        if (!in_array($this->expected_type, [
                "boolean",
                "integer",
                "double",
                "string",
                "array",
                "object",
                "resource",
                "NULL",
                "unknown type",
                "resource (closed)",
            ]) && !class_exists($this->expected_type)) {
            throw new InvalidArgumentException('The expected type must be a valid primitive type or a class name.');
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
     * @throws InvalidArgumentException If the value doesn't match the expected type.
     */
    protected function validate(mixed $value): void
    {
        $type_string = gettype($value);

        if ($type_string === 'object' && $this->expected_type !== 'object') {
            if (!$value instanceof $this->expected_type) {
                throw new InvalidArgumentException(sprintf('Incorrect array item. Must be of type %s, %s given.', $this->expected_type, get_class($value)));
            }
        } else if ($type_string !== $this->expected_type) {
            throw new InvalidArgumentException(sprintf('Incorrect array item. Must be of type %s, %s given.', $this->expected_type, $type_string));
        }
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
     * @param mixed $key The key at which to set the value. Must be null, as keys aren't allowed.
     * @param mixed $value The value to set in the array, which will be validated.
     * @return void
     * @throws InvalidArgumentException If a key other than null is provided.
     */
    public function offsetSet(mixed $key, mixed $value): void
    {
        if (!is_null($key)) {
            throw new InvalidArgumentException('Keys are not allowed in a array.');
        }

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

    public function __toString(): string
    {
        trigger_error("Array to string conversion", E_USER_WARNING);
        return get_class($this);
    }
}