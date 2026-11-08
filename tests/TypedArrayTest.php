<?php

declare(strict_types=1);

namespace TypedArray\Tests;

use PHPUnit\Framework\TestCase;
use stdClass;
use TypeError;
use TypedArray\ArrayArray;
use TypedArray\BooleanArray;
use TypedArray\DoubleArray;
use TypedArray\IntegerArray;
use TypedArray\ObjectArray;
use TypedArray\ResourceArray;
use TypedArray\StringArray;
use TypedArray\TypedArray;

class FixtureUser {}
class FixtureAdminUser extends FixtureUser {}
class FixtureOtherObject {}
class FixtureUserArray extends TypedArray
{
    protected string $expected_type = FixtureUser::class;
}
class FixtureMissingTypeArray extends TypedArray {}
class FixtureInvalidTypeArray extends TypedArray
{
    protected string $expected_type = 'not_a_real_type';
}

final class TypedArrayTest extends TestCase
{
    public function testEmptyCollectionsAreAllowed(): void
    {
        self::assertSame([], (new IntegerArray())->getArrayCopy());
        self::assertSame([], (new FixtureUserArray())->getArrayCopy());
    }

    public function testPrimitiveCollectionsAcceptValuesOfTheirDeclaredType(): void
    {
        $examples = [
            [BooleanArray::class, [true, false]],
            [IntegerArray::class, [0, -12, 42]],
            [DoubleArray::class, [0.0, -1.5, 2.5]],
            [StringArray::class, ['', 'hello']],
            [ArrayArray::class, [[1], ['key' => 'value']]],
            [ObjectArray::class, [new stdClass(), new FixtureOtherObject()]],
        ];

        foreach ($examples as [$class, $values]) {
            $collection = new $class($values);
            self::assertSame($values, $collection->getArrayCopy(), $class);
        }
    }

    public function testPrimitiveCollectionsRejectWrongTypesOnConstruction(): void
    {
        $examples = [
            [BooleanArray::class, 1],
            [IntegerArray::class, '42'],
            [DoubleArray::class, 5],
            [StringArray::class, 7],
            [ArrayArray::class, 'not an array'],
            [ObjectArray::class, []],
            [ResourceArray::class, 'not a resource'],
        ];

        foreach ($examples as [$class, $invalid]) {
            $this->assertTypeError(static function () use ($class, $invalid): void {
                new $class([$invalid]);
            });
        }
    }

    public function testResourceArrayAcceptsOpenResources(): void
    {
        $resource = fopen('php://memory', 'r+');
        self::assertIsResource($resource);

        try {
            $collection = new ResourceArray([$resource]);
            self::assertSame($resource, $collection[0]);
        } finally {
            fclose($resource);
        }
    }

    public function testAppendAndOffsetSetValidateValues(): void
    {
        $numbers = new IntegerArray([10]);
        $numbers->append(20);
        $numbers[] = 30;
        $numbers['answer'] = 42;
        $numbers[0] = -1;

        self::assertSame([0 => -1, 1 => 20, 2 => 30, 'answer' => 42], $numbers->getArrayCopy());

        $this->assertTypeError(static function () use ($numbers): void {
            $numbers->append('invalid');
        });
        $this->assertTypeError(static function () use ($numbers): void {
            $numbers[] = 'invalid';
        });
        $this->assertTypeError(static function () use ($numbers): void {
            $numbers['answer'] = 1.5;
        });

        self::assertSame([0 => -1, 1 => 20, 2 => 30, 'answer' => 42], $numbers->getArrayCopy());
    }

    public function testAssociativeKeysAndIterationWork(): void
    {
        $strings = new StringArray(['admin' => 'Alice', 'editor' => 'Bob']);
        $strings['viewer'] = 'Eve';

        self::assertCount(3, $strings);
        self::assertSame('Alice', $strings['admin']);
        self::assertSame(
            ['admin' => 'Alice', 'editor' => 'Bob', 'viewer' => 'Eve'],
            iterator_to_array($strings)
        );
    }

    public function testRemovalAndCopiedStorage(): void
    {
        $numbers = new IntegerArray([1, 2]);
        $copy = $numbers->getArrayCopy();
        $copy[0] = 'not an integer';
        self::assertSame(1, $numbers[0]);

        unset($numbers[0]);
        self::assertSame([1 => 2], $numbers->getArrayCopy());
    }

    public function testClassCollectionsAcceptSubclasses(): void
    {
        $user = new FixtureUser();
        $admin = new FixtureAdminUser();
        $users = new FixtureUserArray([$user, $admin]);
        $users['third'] = new FixtureAdminUser();

        self::assertSame($user, $users[0]);
        self::assertSame($admin, $users[1]);
        self::assertInstanceOf(FixtureAdminUser::class, $users['third']);
    }

    public function testClassCollectionsRejectUnrelatedObjectsAndScalars(): void
    {
        $users = new FixtureUserArray([new FixtureUser()]);

        $this->assertTypeError(static function () use ($users): void {
            $users[] = new FixtureOtherObject();
        });
        $this->assertTypeError(static function () use ($users): void {
            $users[] = 'not an object';
        });
        self::assertCount(1, $users);
    }

    public function testUndefinedExpectedTypeIsRejected(): void
    {
        $this->expectException(TypeError::class);
        new FixtureMissingTypeArray();
    }

    public function testUnknownExpectedTypeIsRejected(): void
    {
        $this->expectException(TypeError::class);
        new FixtureInvalidTypeArray();
    }

    public function testJsonSerializesIndexedAndAssociativeCollections(): void
    {
        $indexed = new IntegerArray([1, 2, 3]);
        $keyed = new StringArray(['first' => 'one', 'second' => 'two']);

        self::assertSame('[1,2,3]', json_encode($indexed, JSON_THROW_ON_ERROR));
        self::assertSame('{"first":"one","second":"two"}', json_encode($keyed, JSON_THROW_ON_ERROR));
    }

    public function testNativeSerializeRoundTripPreservesTypeChecksForNormalValues(): void
    {
        $numbers = new IntegerArray([1, 2]);
        $restored = unserialize(serialize($numbers), ['allowed_classes' => true]);

        self::assertInstanceOf(IntegerArray::class, $restored);
        self::assertSame([1, 2], $restored->getArrayCopy());
        $this->assertTypeError(static function () use ($restored): void {
            $restored[] = 'not an integer';
        });
    }

    private function assertTypeError(callable $callback): void
    {
        try {
            $callback();
        } catch (TypeError $error) {
            self::assertStringContainsString('type', $error->getMessage());
            return;
        }

        self::fail('Expected a TypeError for a value of the wrong type.');
    }
}
