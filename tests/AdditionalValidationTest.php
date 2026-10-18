<?php

declare(strict_types=1);

namespace TypedArray\Tests;

use PHPUnit\Framework\TestCase;
use TypeError;
use TypedArray\IntegerArray;
use TypedArray\TypedArray;

interface FixtureContract {}
final class FixtureImplementation implements FixtureContract {}
final class FixtureImpostor {}
/** @extends TypedArray<FixtureContract> */
final class FixtureInterfaceArray extends TypedArray
{
    protected string $expected_type = FixtureContract::class;
}
/** @extends TypedArray<resource> */
final class FixtureClosedResourceArray extends TypedArray
{
    protected string $expected_type = 'resource (closed)';
}

final class AdditionalValidationTest extends TestCase
{
    public function testExchangeArrayChecksValuesBeforeReplacingContents(): void
    {
        $numbers = new IntegerArray([1, 2]);
        $old = $numbers->exchangeArray([3, 4]);
        self::assertSame([1, 2], $old);
        self::assertSame([3, 4], $numbers->getArrayCopy());

        try {
            $numbers->exchangeArray(['bad']);
            self::fail('Expected TypeError');
        } catch (TypeError $e) {
            self::assertSame([3, 4], $numbers->getArrayCopy());
        }
    }

    public function testClassInterfaceTypesAcceptImplementations(): void
    {
        $value = new FixtureImplementation();
        $items = new FixtureInterfaceArray([$value]);
        self::assertSame($value, $items[0]);

        $this->expectException(TypeError::class);
        $items[] = new FixtureImpostor();
    }

    public function testClosedResourceIsDistinctFromOpenResource(): void
    {
        $handle = fopen('php://memory', 'r+');
        self::assertIsResource($handle);
        fclose($handle);

        self::assertSame('resource (closed)', gettype($handle));
        $closed = new FixtureClosedResourceArray([$handle]);
        self::assertSame($handle, $closed[0]);
    }


    public function testSerializationCannotReplaceDeclaredType(): void
    {
        $original = new IntegerArray([1]);
        $serialized = $original->__serialize();
        self::assertArrayHasKey(2, $serialized);
        self::assertIsArray($serialized[2]);
        $serialized[2]["\0*\0expected_type"] = 'string';

        $restored = new IntegerArray();
        $this->expectException(TypeError::class);
        $restored->__unserialize($serialized);
    }

    public function testInvalidExchangeArrayFromObject(): void
    {
        $items = new IntegerArray([1]);
        $this->expectException(TypeError::class);
        $items->exchangeArray((object) ['x' => 'bad']);
    }
}
