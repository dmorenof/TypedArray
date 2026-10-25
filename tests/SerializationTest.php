<?php

declare(strict_types=1);

namespace TypedArray\Tests;

use ArrayIterator;
use ArrayObject;
use PHPUnit\Framework\TestCase;
use TypeError;
use TypedArray\TypedArray;

/** @extends TypedArray<int> */
final class FixtureConstructorTypeArray extends TypedArray
{
    public string $label = 'original';

    public function __construct(array $items = [])
    {
        $this->expected_type = 'integer';
        parent::__construct($items);
    }
}

/** @extends TypedArray<int> */
final class FixtureDeclaredTypeArray extends TypedArray
{
    protected string $expected_type = 'integer';
    public string $label = 'original';
}

final class FixtureSerializationIterator extends ArrayIterator {}

final class SerializationTest extends TestCase
{
    public function testConstructorDefinedTypeSurvivesNativeRoundTrip(): void
    {
        $original = new FixtureConstructorTypeArray(['answer' => 42]);
        $original->label = 'saved';
        $restored = unserialize(serialize($original));

        self::assertInstanceOf(FixtureConstructorTypeArray::class, $restored);
        self::assertSame(['answer' => 42], $restored->getArrayCopy());
        self::assertSame('saved', $restored->label);
        $restored[] = 7;
        $this->expectException(TypeError::class);
        $restored[] = 'wrong';
    }

    public function testTypeTamperingLeavesExistingObjectUnchanged(): void
    {
        $target = new FixtureDeclaredTypeArray([10]);
        $payload = $this->replacementPayload();
        $payload[2]["\0*\0expected_type"] = 'string';
        $payload[1] = ['wrong'];

        $this->assertRejectedWithoutMutation($target, $payload);
    }

    public function testInvalidItemsLeaveExistingObjectUnchanged(): void
    {
        $target = new FixtureDeclaredTypeArray([10]);
        $payload = $this->replacementPayload();
        $payload[1] = [20, 'wrong'];

        $this->assertRejectedWithoutMutation($target, $payload);
    }

    public function testValidRestorationPreservesNativeState(): void
    {
        $target = new FixtureDeclaredTypeArray([10]);
        $payload = $this->replacementPayload();
        $target->__unserialize($payload);

        self::assertSame($payload, $target->__serialize());
        self::assertSame(['answer' => 42], $target->getArrayCopy());
        self::assertSame('saved', $target->label);
        self::assertSame(ArrayObject::STD_PROP_LIST, $target->getFlags());
        self::assertSame(FixtureSerializationIterator::class, $target->getIteratorClass());
    }

    public function testUninitializedTypeMustBeRestoredWithAValidDefinition(): void
    {
        foreach ([null, '', 'not_a_valid_type'] as $type) {
            $target = (new \ReflectionClass(FixtureConstructorTypeArray::class))->newInstanceWithoutConstructor();
            $source = new FixtureConstructorTypeArray();
            $payload = $source->__serialize();
            unset($payload[2]["\0*\0expected_type"]);
            if ($type !== null) {
                $payload[2]["\0*\0expected_type"] = $type;
            }

            try {
                $target->__unserialize($payload);
                self::fail('Expected TypeError');
            } catch (TypeError $error) {
                $property = new \ReflectionProperty(TypedArray::class, 'expected_type');
                if (PHP_VERSION_ID < 80100) {
                    $property->setAccessible(true);
                }
                self::assertFalse($property->isInitialized($target));
            }
        }
    }

    private function replacementPayload(): array
    {
        $source = new FixtureDeclaredTypeArray(['answer' => 42]);
        $source->label = 'saved';
        $source->setFlags(ArrayObject::STD_PROP_LIST);
        $source->setIteratorClass(FixtureSerializationIterator::class);

        // Detach native property entries before the source leaves scope (PHP 8.0).
        return unserialize(serialize($source->__serialize()));
    }

    private function assertRejectedWithoutMutation(FixtureDeclaredTypeArray $target, array $payload): void
    {
        $before = $target->__serialize();
        try {
            $target->__unserialize($payload);
            self::fail('Expected TypeError');
        } catch (TypeError $error) {
            self::assertSame($before, $target->__serialize());
        }

        $target[] = 11;
        self::assertSame([10, 11], $target->getArrayCopy());
        $this->expectException(TypeError::class);
        $target[] = 'wrong';
    }
}
