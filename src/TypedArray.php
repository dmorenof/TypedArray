<?php

namespace TypedArray;

use ArrayObject;
use JsonSerializable;
use TypeError;

/**
 * An abstract ArrayObject-based collection with runtime-validated writes.
 *
 * Construction, explicit write methods and unserialization validate item types.
 * Native SPL iterator writes and foreach-by-reference access ArrayObject's
 * inherited storage directly and do not invoke these validators. This native
 * interoperability is intentionally preserved in compatible 1.x versions.
 *
 * See docs/compatibility.md for the exact runtime validation contract.
 *
 * The accepted type is declared in `$expected_type`: one type (`int`, `User::class`), a union (`int|string`,
 * `User|null`) or a nullable (`?int`). See docs/types.md.
 *
 * @template TValue
 * @extends ArrayObject<array-key, TValue>
 */
abstract class TypedArray extends ArrayObject implements JsonSerializable
{
    /** Names that stand for a built-in type, each with the name it is normalized to. */
    private const BUILTIN = [
        'int' => 'integer', 'integer' => 'integer',
        'float' => 'double', 'double' => 'double',
        'string' => 'string',
        'bool' => 'boolean', 'boolean' => 'boolean',
        'array' => 'array',
        'object' => 'object',
        'resource' => 'resource',
        'resource (closed)' => 'resource (closed)',
        'null' => 'NULL',
        'unknown type' => 'unknown type',
        'mixed' => 'mixed',
        'callable' => 'callable',
        'iterable' => 'iterable',
        'false' => 'false',
        'true' => 'true',
    ];

    /** @var array<string, list<string>> The alternatives of each declaration, parsed once. */
    private static array $parsed = [];

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

        $this->accepted();

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
        if ($this->accepts($value)) {
            return;
        }
        $type = gettype($value);
        $this->throwTypeError($type === 'object' && !in_array('object', $this->accepted(), true) ? get_debug_type($value) : $type);
    }

    /** Whether a value would be accepted. Never throws. */
    public function accepts(mixed $value): bool
    {
        foreach ($this->accepted() as $type) {
            if (self::matches($type, $value)) {
                return true;
            }
        }
        return false;
    }

    /** The type declaration as written in the subclass. */
    public function getExpectedType(): string
    {
        return $this->expected_type;
    }

    /**
     * A typed array of any declared type, without writing a subclass.
     *
     * @param array<array-key, mixed> $items
     * @return GenericArray<mixed>
     */
    public static function of(string $type, array $items = []): GenericArray
    {
        return new GenericArray($type, $items);
    }

    /**
     * The items as a plain array, for the functions that only accept one.
     *
     * @return array<array-key, TValue>
     */
    public function toArray(): array
    {
        return $this->getArrayCopy();
    }

    /**
     * The declaration split into alternatives, each a normalized built-in name or a class or interface name.
     *
     * @return list<string>
     */
    private function accepted(): array
    {
        return self::$parsed[$this->expected_type] ??= self::parse($this->expected_type);
    }

    /** @return list<string> */
    private static function parse(string $declaration): array
    {
        $names = explode('|', str_starts_with($declaration, '?') ? substr($declaration, 1) . '|null' : $declaration);
        $types = [];
        foreach ($names as $name) {
            $name = trim($name);
            $builtin = self::BUILTIN[strtolower($name)] ?? null;
            if ($builtin === null) {
                $name = ltrim($name, '\\');
                if ($name === '' || !(class_exists($name) || interface_exists($name))) {
                    throw new TypeError('The expected type must be a valid primitive type, class name, or interface name.');
                }
            }
            $types[] = $builtin ?? $name;
        }
        return array_values(array_unique($types));
    }

    private static function matches(string $type, mixed $value): bool
    {
        return match ($type) {
            'integer' => is_int($value),
            'double' => is_float($value),
            'string' => is_string($value),
            'boolean' => is_bool($value),
            'array' => is_array($value),
            'object' => is_object($value),
            'resource' => is_resource($value),
            'resource (closed)' => gettype($value) === 'resource (closed)',
            'NULL' => $value === null,
            'unknown type' => gettype($value) === 'unknown type',
            'mixed' => true,
            'callable' => is_callable($value),
            'iterable' => is_iterable($value),
            'false' => $value === false,
            'true' => $value === true,
            default => $value instanceof $type,
        };
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
        // Subclasses normally declare a default expected_type. Preserve this
        // contract even if the serialized properties have been tampered with.
        $declaredType = $this->expected_type;
        parent::__unserialize($data);
        if ($this->expected_type !== $declaredType) {
            throw new TypeError('Cannot change the expected array type during unserialization.');
        }

        foreach ($this->getArrayCopy() as $item) {
            $this->validate($item);
        }
    }

    /**
     * Prepares data for JSON serialization.
     *
     * Converts the current object into an array structure suitable for JSON encoding.
     *
     * @return array<array-key, TValue> Returns the array contents.
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