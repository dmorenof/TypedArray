<?php

declare(strict_types=1);

namespace TypedArray\Tests;

use PHPUnit\Framework\TestCase;
use TypeError;
use TypedArray\GenericArray;

/**
 * What a declared type accepts, checked against an oracle written independently of the implementation, over a wide
 * matrix of declarations and values. Whatever way the library checks items (one at a time, a whole list at once, with a
 * compiled plan), it must agree with this.
 */
final class AcceptsEquivalenceTest extends TestCase
{
    private const DECLARATIONS = [
        'int', 'integer', 'float', 'double', 'string', 'bool', 'boolean', 'array', 'object', 'resource', 'null', 'mixed',
        'callable', 'iterable', 'false', 'true',
        'int|string', 'string|int', '?int', 'int|null', '?string', 'bool|null', 'int|float', 'string|array', 'array|object',
        'stdClass', 'ArrayObject', 'Countable', 'Traversable', 'stdClass|null', '?ArrayObject', 'Countable|string', 'ArrayObject|int',
        'object|int', 'iterable|int', 'callable|null', 'callable|int', 'false|int', 'true|false', 'false|null', 'mixed|int',
        'resource|null', 'float|callable', 'bool|callable', 'array|callable', 'object|callable', 'null|callable', 'string|callable', 'int|callable', 'resource|callable', 'int|string|null', 'int|float|string|bool', 'stdClass|ArrayObject', 'iterable|callable',
    ];

    /** @return array<string, mixed> */
    private static function values(): array
    {
        $open = fopen('php://memory', 'r');
        $closed = fopen('php://memory', 'r');
        fclose($closed);
        return [
            'zero' => 0, 'one' => 1, 'negative' => -1, 'big' => PHP_INT_MAX, 'float' => 1.5, 'nan' => NAN, 'infinity' => INF,
            'empty string' => '', 'string' => 'a', 'numeric string' => '12', 'callable string' => 'strlen',
            'true' => true, 'false' => false, 'null' => null,
            'empty array' => [], 'list' => [1, 2], 'callable array' => [new \ArrayObject(), 'count'],
            'stdClass' => new \stdClass(), 'ArrayObject' => new \ArrayObject(), 'ArrayIterator' => new \ArrayIterator([]),
            'closure' => static fn (): int => 1, 'generator' => (static function () { yield 1; })(),
            'open resource' => $open, 'closed resource' => $closed,
        ];
    }

    /** The meaning of a declaration, written out in full and independent of the library. */
    private static function oracle(string $declaration, mixed $value): bool
    {
        $names = explode('|', str_starts_with($declaration, '?') ? substr($declaration, 1) . '|null' : $declaration);
        foreach ($names as $name) {
            $name = ltrim(trim($name), '\\');
            $ok = match (strtolower($name)) {
                'int', 'integer' => is_int($value),
                'float', 'double' => is_float($value),
                'string' => is_string($value),
                'bool', 'boolean' => is_bool($value),
                'array' => is_array($value),
                'object' => is_object($value),
                'resource' => is_resource($value),
                'null' => $value === null,
                'mixed' => true,
                'callable' => is_callable($value),
                'iterable' => is_iterable($value),
                'false' => $value === false,
                'true' => $value === true,
                default => $value instanceof $name,
            };
            if ($ok) {
                return true;
            }
        }
        return false;
    }

    public function testAcceptsAgreesWithTheOracleForEveryDeclarationAndValue(): void
    {
        foreach (self::DECLARATIONS as $declaration) {
            $array = new GenericArray($declaration);
            foreach (self::values() as $label => $value) {
                self::assertSame(self::oracle($declaration, $value), $array->accepts($value), "$declaration with $label");
            }
        }
    }

    public function testAListIsBuiltExactlyWhenEveryItemIsAccepted(): void
    {
        foreach (self::DECLARATIONS as $declaration) {
            foreach (self::values() as $label => $value) {
                $accepted = self::oracle($declaration, $value);
                try {
                    $built = new GenericArray($declaration, [$value, $value]);
                    self::assertTrue($accepted, "$declaration built a list of $label that it does not accept");
                    self::assertCount(2, $built);
                } catch (TypeError $error) {
                    self::assertFalse($accepted, "$declaration refused a list of $label that it accepts");
                    self::assertStringStartsWith('Incorrect array item. Must be of type ' . $declaration . ', ', $error->getMessage());
                }
            }
        }
    }

    public function testOneBadItemAnywhereInAListRefusesTheWholeList(): void
    {
        foreach (self::DECLARATIONS as $declaration) {
            $good = null;
            $bad = null;
            foreach (self::values() as $value) {
                self::oracle($declaration, $value) ? $good ??= [$value] : $bad ??= [$value];
            }
            if ($good === null || $bad === null) {
                continue; // a declaration that accepts, or refuses, every value has no mixed list
            }
            foreach ([[$bad[0], $good[0], $good[0]], [$good[0], $bad[0], $good[0]], [$good[0], $good[0], $bad[0]]] as $items) {
                try {
                    new GenericArray($declaration, $items);
                    self::fail("$declaration accepted a list with one bad item");
                } catch (TypeError) {
                    self::addToAssertionCount(1);
                }
            }
            $array = new GenericArray($declaration, [$good[0]]);
            try {
                $array->exchangeArray([$good[0], $bad[0]]);
                self::fail("$declaration exchanged in a list with one bad item");
            } catch (TypeError) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testASubclassThatOverridesTheChecksIsAskedAboutEveryItem(): void
    {
        $asked = [];
        $array = new class (['a', 'b']) extends \TypedArray\TypedArray {
            /** @var list<mixed> */
            public static array $asked = [];
            protected string $expected_type = 'int';

            protected function validate(mixed $value): void
            {
                self::$asked[] = $value;
            }
        };
        self::assertSame(['a', 'b'], $array::$asked);
        $array->append('c');
        self::assertSame(['a', 'b', 'c'], $array::$asked);
        self::assertCount(3, $array, 'the override decides: nothing here refuses a string in an int collection');
    }

    public function testASubclassThatOverridesAcceptsIsAskedToo(): void
    {
        $array = new class ([2, 4]) extends \TypedArray\IntegerArray {
            public function accepts(mixed $value): bool
            {
                return is_int($value) && $value % 2 === 0;
            }
        };
        self::assertCount(2, $array);
        $this->expectException(TypeError::class);
        new $array([1]);
    }

    public function testMixedAcceptsEverythingAndAnInterfaceOrCallableDeclarationIsChecked(): void
    {
        self::assertCount(3, new GenericArray('mixed', [1, 'a', null]));
        self::assertCount(2, new GenericArray('Countable', [new \ArrayObject(), new \ArrayIterator([])]));
        $this->expectException(TypeError::class);
        new GenericArray('Countable', [new \stdClass()]);
    }

    public function testKeysAreKeptWhenAListIsBuilt(): void
    {
        $array = new GenericArray('int', ['a' => 1, 5 => 2, 'b' => 3]);
        self::assertSame(['a' => 1, 5 => 2, 'b' => 3], $array->getArrayCopy());
    }
}
