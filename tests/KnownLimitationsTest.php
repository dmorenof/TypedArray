<?php

declare(strict_types=1);

namespace TypedArray\Tests;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use TypeError;
use TypedArray\IntegerArray;

/**
 * Contract tests for native SPL write-through interoperability.
 *
 * TypedArray validates its own write methods, but ArrayObject iterators and
 * foreach-by-reference can mutate native storage without invoking them.
 * Those native behaviors remain intentionally supported in compatible 1.x.
 */
final class KnownLimitationsTest extends TestCase
{
    public function testExchangeArrayRejectsValuesOfTheWrongType(): void
    {
        $numbers = new IntegerArray([1]);
        $this->expectException(TypeError::class);
        $numbers->exchangeArray(['wrong']);
    }

    public function testGetIteratorAllowsNativeWritesWithoutTypeValidation(): void
    {
        $numbers = new IntegerArray([1]);
        $iterator = $numbers->getIterator();
        $iterator[0] = 'wrong';

        self::assertSame('wrong', $numbers[0]);
    }

    public function testExternalArrayIteratorAllowsNativeWritesWithoutTypeValidation(): void
    {
        $numbers = new IntegerArray([1]);
        $iterator = new ArrayIterator($numbers);
        $iterator[0] = 'wrong';

        self::assertSame('wrong', $numbers[0]);
    }

    public function testForeachByReferenceAllowsNativeWritesWithoutTypeValidation(): void
    {
        $numbers = new IntegerArray([1]);

        foreach ($numbers as &$value) {
            $value = 'wrong';
        }
        unset($value);

        self::assertSame('wrong', $numbers[0]);
    }

    public function testNativeIteratorWriteDoesNotDisableValidationOfTypedArrayWrites(): void
    {
        $numbers = new IntegerArray([1]);
        $numbers->getIterator()[0] = 'wrong';
        self::assertSame('wrong', $numbers[0]);

        $this->expectException(TypeError::class);
        $numbers[0] = 'also wrong';
    }

    public function testUnserializationCannotInjectWrongTypes(): void
    {
        $numbers = new IntegerArray([777777]);
        $payload = str_replace('i:777777;', 's:5:"wrong";', serialize($numbers));
        self::assertNotSame(serialize($numbers), $payload);
        $this->expectException(TypeError::class);
        unserialize($payload, ['allowed_classes' => true]);
    }

    public function testCastingToStringDoesNotRaiseValueError(): void
    {
        $numbers = new IntegerArray([1]);
        self::assertIsString((string) $numbers);
    }
}
