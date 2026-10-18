<?php

namespace TypedArray;

use ArrayObject;
use JsonSerializable;
use TypeError;

/**
 * An abstract class representing a type-safe array of items with enforced validations.
 *
 * This class extends ArrayObject and validates items through its public write methods.
 *
 * IMPORTANT: Inherited SPL iteration wrappers can access ArrayObject's native
 * storage directly. Until a future major version replaces that storage, this
 * class cannot guarantee type safety for arbitrary external ArrayIterator wrappers.
 *
 * @template TValue
 * @extends ArrayObject<array-key, TValue>
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
     * @param array<array-key, TValue> $items An optional array of items to initialize and validate.
     * @return void
     */
    public function __construct(array $items = [])
    {
        if (empty($this->expected_type)) {
            throw new TypeError('The expected type must be defined in the implementing class.');
        }

        if (!in_array($this->expected_type, self::DEFAULT_PHP_TYPES) && !class_exists($this->expected_type) && !interface_exists($this->expected_type)) {
            throw new TypeError('The expected type must be a valid primitive type, class name, or interface name.');
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
     * @param TValue $value The value to be validated and appended to the array.
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
     * @param TValue $value The value to set in the array, which will be validated.
     * @return void
     * @throws TypeError If the value does not have the expected type.
     */
    public function offsetSet(mixed $key, mixed $value): void
    {
        $this->validate($value);
        parent::offsetSet($key, $value);
    }


    /**
     * Validates every value before replacing the backing array.
     *
     * @param array<array-key, TValue>|object $array
     * @return array<array-key, TValue> The previous contents.
     */
    public function exchangeArray(array|object $array): array
    {
        $items = is_array($array)
            ? $array
            : ($array instanceof \Traversable ? iterator_to_array($array) : (array) $array);

        foreach ($items as $item) {
            $this->validate($item);
        }

        return parent::exchangeArray($items);
    }

    /**
     * Validates the native ArrayObject serialization format on restoration.
     *
     * @param array<mixed> $data
     */
    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);

        foreach ($this->getArrayCopy() as $item) {
            $this->validate($item);
        }
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
     * Returns the class name; triggering E_NOTICE via trigger_error() is
     * unsupported and raises ValueError on modern PHP versions.
     *
     * @return string Returns the class name of the current object.
     */
    public function __toString(): string
    {
        return get_class($this);
    }
}