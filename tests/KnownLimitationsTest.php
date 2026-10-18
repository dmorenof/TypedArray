<?php

declare(strict_types=1);

namespace TypedArray\Tests;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use TypeError;
use TypedArray\IntegerArray;

/**
 * Regression specifications for known holes in ArrayObject inheritance.
 * These tests must be enabled as each issue is fixed; skipped != fixed.
 */
final class KnownLimitationsTest extends TestCase
{
    public function testExchangeArrayRejectsValuesOfTheWrongType(): void
    {
        $this->markTestSkipped('Known issue: inherited exchangeArray() bypasses validate().');

        $numbers = new IntegerArray([1]);
        $this->expectException(TypeError::class);
        $numbers->exchangeArray(['wrong']);
    }

    public function testIteratorCannotMutateUnderlyingTypedStorage(): void
    {
        $this->markTestSkipped('Known issue: getIterator() exposes writable storage.');

        $numbers = new IntegerArray([1]);
        $iterator = $numbers->getIterator();
        $iterator[0] = 'wrong';
        self::assertSame(1, $numbers[0]);
    }

    public function testExternalArrayIteratorCannotMutateUnderlyingTypedStorage(): void
    {
        $this->markTestSkipped('Known issue: constructing an ArrayIterator from an ArrayObject exposes writable storage.');

        $numbers = new IntegerArray([1]);
        $iterator = new ArrayIterator($numbers);
        $iterator[0] = 'wrong';
        self::assertSame(1, $numbers[0]);
    }

    public function testUnserializationCannotInjectWrongTypes(): void
    {
        $this->markTestSkipped('Known issue: restoring serialized ArrayObject state can bypass validation.');

        $numbers = new IntegerArray([1]);
        $payload = str_replace('i:1;', 's:5:"wrong";', serialize($numbers));
        self::assertNotSame(serialize($numbers), $payload);
        $this->expectException(TypeError::class);
        unserialize($payload, ['allowed_classes' => true]);
    }

    public function testCastingToStringDoesNotRaiseValueError(): void
    {
        $this->markTestSkipped('Known issue: __toString() passes E_NOTICE to trigger_error(), which requires a user error level.');

        $numbers = new IntegerArray([1]);
        self::assertIsString((string) $numbers);
    }
}
