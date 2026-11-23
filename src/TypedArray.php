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

    /** @var array<string, string> The test of each built-in type that one function settles. */
    private const TESTS = [
        'integer' => 'is_int', 'double' => 'is_float', 'string' => 'is_string', 'boolean' => 'is_bool',
        'array' => 'is_array', 'object' => 'is_object', 'NULL' => 'is_null',
    ];

    /** @var array<string, true> Built-in types a value's `gettype()` alone decides. */
    private const KINDS = ['integer' => true, 'double' => true, 'string' => true, 'boolean' => true, 'array' => true, 'object' => true, 'NULL' => true];

    /** @var array<string, string> Every normalized built-in name. */
    private const BUILTIN_NORMAL = [
        'integer' => 'integer', 'double' => 'double', 'string' => 'string', 'boolean' => 'boolean', 'array' => 'array',
        'object' => 'object', 'resource' => 'resource', 'resource (closed)' => 'resource (closed)', 'NULL' => 'NULL',
        'unknown type' => 'unknown type', 'mixed' => 'mixed', 'callable' => 'callable', 'iterable' => 'iterable',
        'false' => 'false', 'true' => 'true',
    ];

    /** @var array<string, list<string>> The alternatives of each declaration, parsed once. */
    private static array $parsed = [];

    /** @var array<string, array{string, mixed}> How each declaration is checked, worked out once. */
    private static array $plans = [];

    /** @var array<class-string, bool> Whether a subclass keeps the library's own `validate()` and `accepts()`. */
    private static array $stock = [];

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

        $this->checkAll($items);

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
        $type = \gettype($value);
        $this->throwTypeError($type === 'object' && !\in_array('object', $this->accepted(), true) ? \get_debug_type($value) : $type);
    }

    /** Whether a value would be accepted. Never throws. */
    public function accepts(mixed $value): bool
    {
        [$mode, $data] = self::$plans[$this->expected_type] ?? $this->plan();
        switch ($mode) {
            case 'test':
                return $data($value);
            case 'class':
                return $value instanceof $data;
            case 'kinds':
                return isset($data[\gettype($value)]);
            case 'any':
                return true;
        }
        foreach ($data as $type) {
            if (self::matches($type, $value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * How a declaration is checked, decided once: a single `is_*` test, a single class, a set of `gettype()` names, or
     * the alternatives one by one.
     *
     * @return array{string, mixed}
     */
    private function plan(): array
    {
        $types = $this->accepted();
        $type = $types[0];
        $plan = match (true) {
            \count($types) === 1 && $type === 'mixed' => ['any', null],
            \count($types) === 1 && isset(self::TESTS[$type]) => ['test', self::TESTS[$type]],
            \count($types) === 1 && !isset(self::BUILTIN_NORMAL[$type]) => ['class', $type],
            ($kinds = self::kinds($types)) !== null => ['kinds', $kinds],
            default => ['loop', $types],
        };
        return self::$plans[$this->expected_type] = $plan;
    }

    /**
     * Checks a whole list. A class that keeps the library's own checks gets one tight loop for the common declarations;
     * one that overrides `validate()` or `accepts()` is asked about each item, as ever. Either way the first bad item
     * is reported by `validate()`, so the message is the same.
     *
     * @param array<array-key, mixed> $items
     */
    private function checkAll(array $items): void
    {
        $types = $this->accepted();
        if (!(self::$stock[static::class] ??= $this->keepsStockChecks())) {
            foreach ($items as $item) {
                $this->validate($item);
            }
            return;
        }
        $type = $types[0];
        $count = \count($types);
        $bad = null;
        if ($count === 1 && $type === 'mixed') {
            return;
        }
        if ($count === 1 && $type === 'integer') {
            foreach ($items as $item) {
                if (!\is_int($item)) {
                    $bad = [$item];
                    break;
                }
            }
        } elseif ($count === 1 && $type === 'string') {
            foreach ($items as $item) {
                if (!\is_string($item)) {
                    $bad = [$item];
                    break;
                }
            }
        } elseif ($count === 1 && ($function = self::TESTS[$type] ?? null) !== null) {
            foreach ($items as $item) {
                if (!$function($item)) {
                    $bad = [$item];
                    break;
                }
            }
        } elseif ($count === 1 && !isset(self::BUILTIN_NORMAL[$type])) {
            foreach ($items as $item) {
                if (!$item instanceof $type) {
                    $bad = [$item];
                    break;
                }
            }
        } elseif (($kinds = self::kinds($types)) !== null) {
            foreach ($items as $item) {
                if (!isset($kinds[\gettype($item)])) {
                    $bad = [$item];
                    break;
                }
            }
        } else {
            foreach ($items as $item) {
                if (!$this->accepts($item)) {
                    $bad = [$item];
                    break;
                }
            }
        }
        if ($bad !== null) {
            $this->validate($bad[0]);
        }
    }

    /**
     * The `gettype()` names a declaration accepts, when the type of a value alone decides it (no class, `callable`,
     * `iterable`, `false` or `true` among the alternatives); null otherwise.
     *
     * @param list<string> $types
     * @return array<string, true>|null
     */
    private static function kinds(array $types): ?array
    {
        $kinds = [];
        foreach ($types as $type) {
            if (!isset(self::KINDS[$type])) {
                return null;
            }
            $kinds[$type === 'integer' ? 'integer' : $type] = true;
        }
        return $kinds;
    }

    private function keepsStockChecks(): bool
    {
        return (new \ReflectionMethod($this, 'validate'))->getDeclaringClass()->getName() === self::class
            && (new \ReflectionMethod($this, 'accepts'))->getDeclaringClass()->getName() === self::class;
    }

    /** The type declaration as written in the subclass. */
    public function getExpectedType(): string
    {
        return $this->expected_type;
    }

    /** Whether the collection holds no items. */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * The first item, or null when empty.
     *
     * @return TValue|null
     */
    public function first(): mixed
    {
        foreach ($this as $item) {
            return $item;
        }
        return null;
    }

    /**
     * The last item, or null when empty.
     *
     * @return TValue|null
     */
    public function last(): mixed
    {
        $items = $this->getArrayCopy();
        return $items === [] ? null : $items[array_key_last($items)];
    }

    /**
     * The items the callback keeps (those that are truthy when there is none), in a collection of the same class and
     * type. Keys are preserved.
     *
     * @param (callable(TValue): bool)|null $keep
     * @return static
     */
    public function filter(?callable $keep = null): static
    {
        $items = $this->getArrayCopy();
        $copy = clone $this;
        $copy->exchangeArray($keep === null ? array_filter($items) : array_filter($items, $keep));
        return $copy;
    }

    /**
     * The result of the callback for each item, as a plain array (the results need not be of the declared type). Keys
     * are preserved.
     *
     * @template TResult
     * @param callable(TValue): TResult $transform
     * @return array<array-key, TResult>
     */
    public function map(callable $transform): array
    {
        $results = [];
        foreach ($this->getArrayCopy() as $key => $item) {
            $results[$key] = $transform($item);
        }
        return $results;
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
            'integer' => \is_int($value),
            'double' => \is_float($value),
            'string' => \is_string($value),
            'boolean' => \is_bool($value),
            'array' => \is_array($value),
            'object' => \is_object($value),
            'resource' => \is_resource($value),
            'resource (closed)' => \gettype($value) === 'resource (closed)',
            'NULL' => $value === null,
            'unknown type' => \gettype($value) === 'unknown type',
            'mixed' => true,
            'callable' => \is_callable($value),
            'iterable' => \is_iterable($value),
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

        $this->checkAll($items);

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

        $this->checkAll($this->getArrayCopy());
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