<?php

namespace TypedArray;

use ArrayObject;
use JsonSerializable;
use TypeError;

/**
 * An array that only accepts values of one declared type, or of several (a union).
 *
 * A subclass declares the accepted type in `$expected_type`:
 *
 *   protected string $expected_type = 'int';              // one type
 *   protected string $expected_type = 'int|string';       // a union
 *   protected string $expected_type = '?App\User';        // nullable class
 *   protected string $expected_type = App\Shape::class;   // class, interface or enum
 *
 * Accepted names: int, float, string, bool, array, object, resource, null, mixed, callable, iterable, false, true,
 * and any class, interface or enum. The names gettype() returns (integer, double, boolean, NULL) still work.
 * Values are never converted: an int is not a float.
 *
 * It extends ArrayObject, because PHP does not allow extending the array type itself. It can be counted,
 * iterated, read and written with [] and passed to json_encode(); toArray() gives the plain array for the
 * functions that only accept an array.
 *
 * @extends ArrayObject<array-key, mixed>
 */
abstract class TypedArray extends ArrayObject implements JsonSerializable
{
    /** Names that stand for a built-in type, with the name each one is normalized to. */
    private const BUILTIN = [
        'int' => 'int', 'integer' => 'int',
        'float' => 'float', 'double' => 'float',
        'string' => 'string',
        'bool' => 'bool', 'boolean' => 'bool',
        'array' => 'array',
        'object' => 'object',
        'resource' => 'resource', 'resource (closed)' => 'resource',
        'null' => 'null',
        'mixed' => 'mixed',
        'callable' => 'callable',
        'iterable' => 'iterable',
        'false' => 'false',
        'true' => 'true',
    ];

    /** @var array<string, list<string>> Parsed accepted types by declaration, so each one is parsed once. */
    private static array $parsed = [];

    protected string $expected_type;

    /**
     * @param array<mixed> $items Initial items; each one is checked.
     * @throws TypeError If the declared type is missing or unknown, or an item does not match it.
     */
    public function __construct(array $items = [])
    {
        if (empty($this->expected_type)) {
            throw new TypeError('The expected type must be defined in the implementing class.');
        }
        $this->accepted();
        $this->assertAll($items);
        parent::__construct($items);
    }

    /**
     * A typed array of any type, without writing a subclass.
     *
     * @param array<mixed> $items
     */
    public static function of(string $type, array $items = []): GenericArray
    {
        return new GenericArray($type, $items);
    }

    /** The type declaration as written in the subclass. */
    public function getExpectedType(): string
    {
        return $this->expected_type;
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

    /**
     * Checks a value against the declared type.
     *
     * @throws TypeError If the value does not match.
     */
    protected function validate(mixed $value): void
    {
        if (!$this->accepts($value)) {
            throw new TypeError(sprintf(
                'Incorrect array item. Must be of type %s, %s given.',
                $this->expected_type,
                get_debug_type($value)
            ));
        }
    }

    public function append(mixed $value): void
    {
        $this->validate($value);
        parent::append($value);
    }

    public function offsetSet(mixed $key, mixed $value): void
    {
        $this->validate($value);
        parent::offsetSet($key, $value);
    }

    /**
     * Replaces every item. The new items are checked first, so a failure leaves the array unchanged.
     *
     * @param array<mixed>|object $array
     * @return array<mixed> The previous items.
     */
    public function exchangeArray(array|object $array): array
    {
        $this->assertAll(is_array($array) ? $array : get_object_vars($array));
        return parent::exchangeArray($array);
    }

    /** @param array<mixed> $data */
    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        $this->assertAll($this->getArrayCopy());
    }

    /**
     * The items as a plain array, for the functions that only accept one.
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return $this->getArrayCopy();
    }

    /** @return array<array-key, mixed> */
    public function jsonSerialize(): array
    {
        return $this->getArrayCopy();
    }

    /**
     * To simulate the default behavior of an array, raises "Array to string conversion" (a user warning: PHP
     * refuses to raise E_NOTICE from user code) and returns the class name.
     */
    public function __toString(): string
    {
        trigger_error('Array to string conversion', E_USER_WARNING);
        return get_class($this);
    }

    /** @param array<mixed> $items */
    private function assertAll(array $items): void
    {
        foreach ($items as $item) {
            $this->validate($item);
        }
    }

    /**
     * The declared type split into its alternatives, in normalized form: a built-in name or a class name.
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
                if ($name === '' || !(class_exists($name) || interface_exists($name) || enum_exists($name))) {
                    throw new TypeError('The expected type must be a valid primitive type or a class name.');
                }
            }
            $types[] = $builtin ?? $name;
        }
        return array_values(array_unique($types));
    }

    private static function matches(string $type, mixed $value): bool
    {
        return match ($type) {
            'int' => is_int($value),
            'float' => is_float($value),
            'string' => is_string($value),
            'bool' => is_bool($value),
            'array' => is_array($value),
            'object' => is_object($value),
            'resource' => is_resource($value) || gettype($value) === 'resource (closed)',
            'null' => $value === null,
            'mixed' => true,
            'callable' => is_callable($value),
            'iterable' => is_iterable($value),
            'false' => $value === false,
            'true' => $value === true,
            default => $value instanceof $type,
        };
    }
}
