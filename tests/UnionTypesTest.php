<?php

declare(strict_types=1);

namespace TypedArray\Tests;

use PHPUnit\Framework\TestCase;
use TypeError;
use TypedArray\GenericArray;
use TypedArray\IntegerArray;
use TypedArray\TypedArray;

interface UnionShape {}
final class UnionSquare implements UnionShape {}

/** @extends TypedArray<int|string> */
final class IntOrStringArray extends TypedArray
{
    protected string $expected_type = 'int|string';
}
/** @extends TypedArray<int|null> */
final class MaybeIntArray extends TypedArray
{
    protected string $expected_type = '?int';
}
/** @extends TypedArray<mixed> */
final class EmptyMemberArray extends TypedArray
{
    protected string $expected_type = 'int||string';
}

final class UnionTypesTest extends TestCase
{
    public function testAUnionAcceptsEveryMemberOnEveryWritePath(): void
    {
        $values = new IntOrStringArray([1, 'a']);
        $values[] = 2;
        $values['k'] = 'b';
        $values->append(3);
        $values->exchangeArray([4, 'c']);
        self::assertSame([4, 'c'], $values->toArray());
    }

    public function testAUnionRejectsOtherTypesOnEveryWritePath(): void
    {
        $writes = [
            static fn () => new IntOrStringArray([1.5]),
            static fn () => (new IntOrStringArray())->append(null),
            static function (): void { $a = new IntOrStringArray(); $a[] = []; },
            static function (): void { $a = new IntOrStringArray(); $a['k'] = new \stdClass(); },
            static fn () => (new IntOrStringArray())->exchangeArray([true]),
        ];
        foreach ($writes as $index => $write) {
            try {
                $write();
                self::fail("Write $index should have been refused.");
            } catch (TypeError $error) {
                self::assertStringContainsString('Must be of type int|string', $error->getMessage());
            }
        }
    }

    public function testTheErrorNamesTheGivenType(): void
    {
        $this->expectExceptionMessage('Incorrect array item. Must be of type int|string, double given.');
        new IntOrStringArray([1.5]);
    }

    public function testNullable(): void
    {
        self::assertCount(2, new MaybeIntArray([1, null]));
        self::assertCount(2, TypedArray::of('int|null', [1, null]));
        $this->expectException(TypeError::class);
        new MaybeIntArray(['x']);
    }

    public function testPhpTypeNamesWorkLikeTheGettypeNames(): void
    {
        self::assertCount(1, TypedArray::of('int', [1]));
        self::assertCount(1, TypedArray::of('integer', [1]));
        self::assertCount(1, TypedArray::of('float', [1.5]));
        self::assertCount(1, TypedArray::of('double', [1.5]));
        self::assertCount(1, TypedArray::of('bool', [false]));
        self::assertCount(1, TypedArray::of('boolean', [false]));
        $this->expectException(TypeError::class);
        TypedArray::of('float', [1]); // an int is not converted
    }

    public function testCallableIterableMixedAndLiteralBooleans(): void
    {
        self::assertCount(2, TypedArray::of('callable', ['strlen', static fn () => 1]));
        self::assertCount(2, TypedArray::of('iterable', [[1], new \ArrayIterator([])]));
        self::assertCount(3, TypedArray::of('mixed', [1, 'a', null]));
        self::assertCount(1, TypedArray::of('false', [false]));
        $this->expectException(TypeError::class);
        TypedArray::of('true', [false]);
    }

    public function testClassesAndInterfacesInsideUnionsAndWithALeadingBackslash(): void
    {
        $values = TypedArray::of('\\' . UnionSquare::class . '|' . UnionShape::class . '|string', [new UnionSquare(), 'x']);
        self::assertCount(2, $values);
        $this->expectException(TypeError::class);
        $values[] = new \stdClass();
    }

    public function testOpenAndClosedResourcesStayDistinct(): void
    {
        $open = fopen('php://memory', 'r');
        $closed = fopen('php://memory', 'r');
        fclose($closed);
        self::assertCount(2, TypedArray::of('resource|resource (closed)', [$open, $closed]));
        $this->expectException(TypeError::class);
        TypedArray::of('resource', [$closed]);
    }

    public function testBadDeclarationsAreRefusedWhenTheArrayIsCreated(): void
    {
        foreach (['NoSuchClass', 'int||string', 'int|NoSuchClass', '?', ''] as $declaration) {
            try {
                TypedArray::of($declaration);
                self::fail("'$declaration' should have been refused.");
            } catch (TypeError) {
                self::assertTrue(true);
            }
        }
        $this->expectException(TypeError::class);
        new EmptyMemberArray();
    }

    public function testAcceptsNeverThrows(): void
    {
        $values = new IntOrStringArray();
        self::assertTrue($values->accepts(1));
        self::assertFalse($values->accepts(null));
        self::assertSame('int|string', $values->getExpectedType());
    }

    public function testToArrayAndTheFactory(): void
    {
        $ids = new GenericArray('int', [3, 4]);
        self::assertSame([3, 4], $ids->toArray());
        self::assertSame([5], TypedArray::of('int', [5])->toArray());
        self::assertSame([1], (new IntegerArray([1]))->toArray());
    }

    public function testSerializationRoundTripKeepsUnionChecks(): void
    {
        $restored = unserialize(serialize(new IntOrStringArray([1, 'a'])));
        self::assertInstanceOf(IntOrStringArray::class, $restored);
        self::assertSame([1, 'a'], $restored->toArray());
        $tampered = str_replace('a:2:{i:0;i:1;i:1;s:1:"a";}', 'a:2:{i:0;i:1;i:1;d:1.5;}', serialize(new IntOrStringArray([1, 'a'])));
        self::assertNotSame(serialize(new IntOrStringArray([1, 'a'])), $tampered);
        $this->expectException(TypeError::class);
        unserialize($tampered);
    }
}
