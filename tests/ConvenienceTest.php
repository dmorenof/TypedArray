<?php

declare(strict_types=1);

namespace TypedArray\Tests;

use PHPUnit\Framework\TestCase;
use TypedArray\{GenericArray, IntegerArray};

final class ConvenienceTest extends TestCase
{
    public function testEmptyCollection(): void
    {
        $array = new IntegerArray();
        self::assertTrue($array->isEmpty());
        self::assertNull($array->first());
        self::assertNull($array->last());
        self::assertSame([], $array->filter()->getArrayCopy());
        self::assertSame([], $array->map(static fn (int $i): int => $i));
    }

    public function testFirstAndLastFollowTheOrderNotTheKeys(): void
    {
        $array = new IntegerArray(['b' => 2, 'a' => 1, 9 => 3]);
        self::assertFalse($array->isEmpty());
        self::assertSame(2, $array->first());
        self::assertSame(3, $array->last());
    }

    public function testFilterKeepsClassTypeAndKeys(): void
    {
        $array = new IntegerArray(['a' => 1, 'b' => 2, 'c' => 3]);
        $even = $array->filter(static fn (int $v): bool => $v % 2 === 0);
        self::assertInstanceOf(IntegerArray::class, $even);
        self::assertSame(['b' => 2], $even->getArrayCopy());
        self::assertCount(3, $array, 'the original is untouched');
    }

    public function testFilterWithoutCallbackDropsFalsyItems(): void
    {
        self::assertSame([1 => 1, 3 => 5], (new IntegerArray([0, 1, 0, 5]))->filter()->getArrayCopy());
    }

    public function testFilterWorksOnAGenericArrayWithItsType(): void
    {
        $filtered = (new GenericArray('int|string', [1, 'a', 2]))->filter('is_int');
        self::assertSame('int|string', $filtered->getExpectedType());
        self::assertSame([0 => 1, 2 => 2], $filtered->getArrayCopy());
        $this->expectException(\TypeError::class);
        $filtered[] = 1.5;
    }

    public function testMapReturnsAPlainArrayWithKeys(): void
    {
        $array = new IntegerArray(['a' => 1, 'b' => 2]);
        self::assertSame(['a' => '1!', 'b' => '2!'], $array->map(static fn (int $v): string => $v . '!'));
    }
}
