<?php

declare(strict_types=1);

namespace TypedArray\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TypedArray\ArrayArray;
use TypedArray\BooleanArray;
use TypedArray\DoubleArray;
use TypedArray\GenericArray;
use TypedArray\IntegerArray;
use TypedArray\ObjectArray;
use TypedArray\ResourceArray;
use TypedArray\StringArray;
use TypedArray\TypedArray;

interface Shape {}
class Square implements Shape {}
class Circle implements Shape {}
enum Suit: string { case Hearts = 'h'; }

final class Shapes extends TypedArray { protected string $expected_type = Shape::class; }
final class Suits extends TypedArray { protected string $expected_type = Suit::class; }
final class Mixed1 extends TypedArray { protected string $expected_type = 'int|string'; }
final class MaybeInt extends TypedArray { protected string $expected_type = '?int'; }
final class Legacy extends TypedArray { protected string $expected_type = 'integer'; }
final class NoType extends TypedArray {}
final class UnknownType extends TypedArray { protected string $expected_type = 'NoSuchClass'; }
final class EmptyUnionMember extends TypedArray { protected string $expected_type = 'int||string'; }

final class TypedArrayTest extends TestCase
{
    public function testDefaultArraysAcceptTheirType(): void
    {
        self::assertCount(2, new IntegerArray([1, 2]));
        self::assertCount(1, new StringArray(['a']));
        self::assertCount(1, new DoubleArray([1.5]));
        self::assertCount(1, new BooleanArray([false]));
        self::assertCount(1, new ArrayArray([[1]]));
        self::assertCount(1, new ObjectArray([new \stdClass()]));
        $file = fopen('php://memory', 'r');
        self::assertCount(1, new ResourceArray([$file]));
    }

    /** @return iterable<string, array{class-string<TypedArray>, mixed}> */
    public static function rejected(): iterable
    {
        yield 'string in integers' => [IntegerArray::class, 'x'];
        yield 'int in doubles (no conversion)' => [DoubleArray::class, 1];
        yield 'null in integers' => [IntegerArray::class, null];
        yield 'int in strings' => [StringArray::class, 1];
        yield 'object in arrays' => [ArrayArray::class, new \stdClass()];
        yield 'foreign class in interface array' => [Shapes::class, new \stdClass()];
        yield 'string in enum array' => [Suits::class, 'h'];
        yield 'float in int|string' => [Mixed1::class, 1.5];
        yield 'string in ?int' => [MaybeInt::class, 'x'];
    }

    #[DataProvider('rejected')]
    public function testRejectsWrongTypesEverywhereAnItemEnters(string $class, mixed $bad): void
    {
        $viaConstructor = false;
        try { new $class([$bad]); } catch (\TypeError) { $viaConstructor = true; }
        $viaAppend = false;
        try { (new $class())->append($bad); } catch (\TypeError) { $viaAppend = true; }
        $viaOffset = false;
        try { $a = new $class(); $a['k'] = $bad; } catch (\TypeError) { $viaOffset = true; }
        $viaPush = false;
        try { $a = new $class(); $a[] = $bad; } catch (\TypeError) { $viaPush = true; }
        $viaExchange = false;
        try { (new $class())->exchangeArray([$bad]); } catch (\TypeError) { $viaExchange = true; }

        self::assertSame([true, true, true, true, true], [$viaConstructor, $viaAppend, $viaOffset, $viaPush, $viaExchange]);
    }

    public function testErrorNamesTheDeclaredAndTheGivenType(): void
    {
        $this->expectExceptionMessage('Incorrect array item. Must be of type int|string, float given.');
        new Mixed1([1.5]);
    }

    public function testInterfacesAndEnumsAreValidTypes(): void
    {
        $shapes = new Shapes([new Square(), new Circle()]);
        self::assertCount(2, $shapes);
        self::assertCount(1, new Suits([Suit::Hearts]));
    }

    public function testUnionAcceptsEachMember(): void
    {
        $values = new Mixed1([1, 'a']);
        $values[] = 2;
        $values['k'] = 'b';
        self::assertSame([1, 'a', 2, 'k' => 'b'], $values->toArray());
    }

    public function testNullableAndExplicitNull(): void
    {
        self::assertCount(2, new MaybeInt([1, null]));
        self::assertCount(2, TypedArray::of('int|null', [1, null]));
    }

    public function testGettypeNamesStillWork(): void
    {
        self::assertCount(1, new Legacy([1]));
        self::assertCount(1, TypedArray::of('double', [1.5]));
        self::assertCount(1, TypedArray::of('boolean', [true]));
    }

    public function testCallableIterableMixedAndLiteralBooleans(): void
    {
        self::assertCount(2, TypedArray::of('callable', ['strlen', fn () => 1]));
        self::assertCount(2, TypedArray::of('iterable', [[1], new \ArrayIterator([])]));
        self::assertCount(3, TypedArray::of('mixed', [1, 'a', null]));
        self::assertCount(1, TypedArray::of('false', [false]));
        $this->expectException(\TypeError::class);
        TypedArray::of('true', [false]);
    }

    public function testClassNamesMayStartWithABackslash(): void
    {
        self::assertCount(1, TypedArray::of('\\' . Square::class, [new Square()]));
    }

    public function testDeclarationErrors(): void
    {
        foreach ([NoType::class => 'must be defined', UnknownType::class => 'valid primitive type or a class name', EmptyUnionMember::class => 'valid primitive type or a class name'] as $class => $message) {
            try {
                new $class();
                self::fail("$class should have been refused.");
            } catch (\TypeError $error) {
                self::assertStringContainsString($message, $error->getMessage());
            }
        }
    }

    public function testFailedExchangeLeavesTheArrayUnchanged(): void
    {
        $values = new IntegerArray([1, 2]);
        try { $values->exchangeArray([3, 'x']); } catch (\TypeError) {}
        self::assertSame([1, 2], $values->toArray());
        self::assertSame([1, 2], $values->exchangeArray([5]));
        self::assertSame([5], $values->toArray());
    }

    public function testBehavesLikeAnArray(): void
    {
        $values = new StringArray(['b' => 'x', 'a' => 'y']);
        self::assertSame(2, count($values));
        self::assertTrue(isset($values['a']));
        self::assertSame('y', $values['a']);
        unset($values['a']);
        self::assertFalse(isset($values['a']));
        self::assertSame(['b' => 'x'], (array) $values);
        self::assertSame('{"b":"x"}', json_encode($values));
        self::assertSame(['b' => 'x'], [...$values]);
        self::assertSame(['b' => 'x'], iterator_to_array($values));
        $copy = clone $values;
        $copy[] = 'new';
        self::assertCount(1, $values);
    }

    public function testSerializationKeepsTheTypeCheck(): void
    {
        $values = new IntegerArray([1]);
        self::assertSame([1], unserialize(serialize($values))->toArray());
        $tampered = str_replace('a:1:{i:0;i:1;}', 'a:1:{i:0;s:1:"x";}', serialize($values));
        self::assertNotSame(serialize($values), $tampered);
        $this->expectException(\TypeError::class);
        unserialize($tampered);
    }

    public function testToStringRaisesTheArrayConversionWarning(): void
    {
        $caught = null;
        set_error_handler(static function (int $number, string $message) use (&$caught): bool { $caught = $message; return true; });
        try {
            self::assertSame(IntegerArray::class, (string) new IntegerArray());
        } finally {
            restore_error_handler();
        }
        self::assertSame('Array to string conversion', $caught);
    }

    public function testAcceptsNeverThrows(): void
    {
        $values = new Mixed1();
        self::assertTrue($values->accepts(1));
        self::assertFalse($values->accepts(null));
        self::assertSame('int|string', $values->getExpectedType());
    }

    public function testSubclassWithAdditionalRulesCanReuseValidate(): void
    {
        $positive = new class([1]) extends IntegerArray {
            public function append(mixed $value): void
            {
                $this->validate($value);
                if ($value < 0) {
                    throw new \RangeException('negative');
                }
                parent::append($value);
            }
        };
        $this->expectException(\RangeException::class);
        $positive->append(-1);
    }
}
