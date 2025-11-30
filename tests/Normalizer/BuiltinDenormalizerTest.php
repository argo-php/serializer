<?php

declare(strict_types=1);

namespace ArgoTest\Serializer\Normalizer;

use Argo\EntityDefinition\TypeReflector\VariableTypeReflector;
use Argo\Serializer\Normalizer\BuiltinDenormalizer;
use Argo\Types\Alias\FalseType;
use Argo\Types\Alias\TrueType;
use Argo\Types\Atomic\ArrayType;
use Argo\Types\Atomic\BoolType;
use Argo\Types\Atomic\ClassType;
use Argo\Types\Atomic\FloatType;
use Argo\Types\Atomic\IntType;
use Argo\Types\Atomic\MixedType;
use Argo\Types\Atomic\NullType;
use Argo\Types\Atomic\ObjectType;
use Argo\Types\Atomic\StringType;
use ArgoTest\Serializer\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BuiltinDenormalizer::class)]
class BuiltinDenormalizerTest extends TestCase
{
    public function testDenormalize()
    {
        $denormalizer = new BuiltinDenormalizer(new VariableTypeReflector());

        $this->assertEquals((object) ['foo' => 'bar'], $denormalizer->denormalize(['foo' => 'bar'], new ObjectType()));
        $this->assertSame('123', $denormalizer->denormalize(123, new StringType()));
        $this->assertSame('123.1', $denormalizer->denormalize(123.10, new StringType()));
        $this->assertSame(123, $denormalizer->denormalize(123, new MixedType()));
        $this->assertSame(null, $denormalizer->denormalize(null, new NullType()));
        $this->assertSame(123., $denormalizer->denormalize(123, new FloatType()));
        $this->assertSame(123.23, $denormalizer->denormalize('123.23', new FloatType()));
        $this->assertSame(true, $denormalizer->denormalize('1', new BoolType()));
        $this->assertSame(true, $denormalizer->denormalize(true, new BoolType()));
        $this->assertSame(1, $denormalizer->denormalize(true, new IntType()));
        $this->assertSame(12, $denormalizer->denormalize('12', new IntType()));
    }

    public function testSupportsDenormalization()
    {
        $denormalizer = new BuiltinDenormalizer(new VariableTypeReflector());

        $this->assertTrue($denormalizer->supportsDenormalization('mixed', new ObjectType()));
        $this->assertTrue($denormalizer->supportsDenormalization('mixed', new StringType()));
        $this->assertTrue($denormalizer->supportsDenormalization('mixed', new MixedType()));
        $this->assertTrue($denormalizer->supportsDenormalization('mixed', new NullType()));
        $this->assertTrue($denormalizer->supportsDenormalization('mixed', new FloatType()));
        $this->assertTrue($denormalizer->supportsDenormalization('mixed', new BoolType()));
        $this->assertTrue($denormalizer->supportsDenormalization('mixed', new IntType()));
        $this->assertTrue($denormalizer->supportsDenormalization('mixed', new TrueType()));
        $this->assertTrue($denormalizer->supportsDenormalization('mixed', new FalseType()));
        $this->assertFalse($denormalizer->supportsDenormalization('mixed', new ArrayType()));
        $this->assertFalse($denormalizer->supportsDenormalization('mixed', new ClassType('class')));
    }
}
