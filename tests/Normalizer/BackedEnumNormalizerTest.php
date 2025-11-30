<?php

declare(strict_types=1);

namespace ArgoTest\Serializer\Normalizer;

use Argo\Serializer\Context\BackedEnumContext;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Exception\Validation\IncorrectTypeException;
use Argo\Serializer\Exception\Validation\UnexpectedEnumValueException;
use Argo\Serializer\Normalizer\BackedEnumNormalizer;
use Argo\Types\Atomic\ClassType;
use Argo\Types\Atomic\IntType;
use ArgoTest\Serializer\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BackedEnumNormalizer::class)]
class BackedEnumNormalizerTest extends TestCase
{
    private BackedEnumNormalizer $normalizer;

    public function setUp(): void
    {
        $this->normalizer = new BackedEnumNormalizer();
    }

    public function testNormalize()
    {
        $this->assertEquals(StringEnumStub::Foo->value, $this->normalizer->normalize(StringEnumStub::Foo));
    }

    public function testDenormalize()
    {
        $actual = $this->normalizer->denormalize('foo', new ClassType(StringEnumStub::class));
        $this->assertEquals(StringEnumStub::Foo, $actual);
    }

    public function testDenormalizeAllowInvalidValue()
    {
        $context = new ContextBag(
            new BackedEnumContext(true),
        );

        $this->assertNull(
            $this->normalizer->denormalize(
                'nothing',
                new ClassType(StringEnumStub::class),
                contextBag: $context,
            ),
        );
        $this->assertNull(
            $this->normalizer->denormalize(1, new ClassType(StringEnumStub::class), contextBag: $context),
        );
        $this->assertNull(
            $this->normalizer->denormalize(
                'nothing',
                new ClassType(IntEnumStub::class),
                contextBag: $context,
            ),
        );
    }

    public function testDenormalizeUnexpectedEnumValue()
    {
        $this->expectException(UnexpectedEnumValueException::class);
        $this->normalizer->denormalize('nothing', new ClassType(StringEnumStub::class));
    }

    public function testDenormalizeNumericStringEnum()
    {
        $actual = $this->normalizer->denormalize('1', new ClassType(NumericStringEnumStub::class));
        $this->assertEquals(NumericStringEnumStub::Foo, $actual);

        $actual = $this->normalizer->denormalize(1, new ClassType(NumericStringEnumStub::class));
        $this->assertEquals(NumericStringEnumStub::Foo, $actual);
    }

    public function testDenormalizeIntEnumFromNumericString()
    {
        $actual = $this->normalizer->denormalize('1', new ClassType(IntEnumStub::class));
        $this->assertEquals(IntEnumStub::Foo, $actual);

        $actual = $this->normalizer->denormalize(1, new ClassType(IntEnumStub::class));
        $this->assertEquals(IntEnumStub::Foo, $actual);
    }

    public function testDenormalizeIncorrectIntType()
    {
        $this->expectException(IncorrectTypeException::class);
        $this->normalizer->denormalize(true, new ClassType(IntEnumStub::class));

        $this->expectException(IncorrectTypeException::class);
        $this->normalizer->denormalize('nothing', new ClassType(IntEnumStub::class));
    }

    public function testSupportsNormalization()
    {
        $this->assertTrue($this->normalizer->supportsNormalization(StringEnumStub::Foo));
        $this->assertTrue($this->normalizer->supportsNormalization(IntEnumStub::Foo));
        $this->assertFalse($this->normalizer->supportsNormalization(EnumStub::Foo));
        $this->assertFalse($this->normalizer->supportsNormalization($this));
        $this->assertFalse($this->normalizer->supportsNormalization(123));
    }

    public function testSupportsDenormalization()
    {
        $this->assertTrue($this->normalizer->supportsDenormalization('value', new ClassType(StringEnumStub::class)));
        $this->assertTrue($this->normalizer->supportsDenormalization('value', new ClassType(IntEnumStub::class)));
        $this->assertFalse($this->normalizer->supportsDenormalization('value', new ClassType(EnumStub::class)));
        $this->assertFalse($this->normalizer->supportsDenormalization('value', new ClassType(self::class)));
        $this->assertFalse($this->normalizer->supportsDenormalization('value', new IntType()));
    }
}

enum StringEnumStub: string
{
    case Foo = 'foo';
    case Bar = 'bar';
}

enum IntEnumStub: int
{
    case Foo = 1;
    case Bar = 2;
}

enum NumericStringEnumStub: string
{
    case Foo = '1';
    case Bar = '2';
}

enum EnumStub
{
    case Foo;
    case Bar;
}
