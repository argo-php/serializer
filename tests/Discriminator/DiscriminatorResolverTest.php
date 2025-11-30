<?php

declare(strict_types=1);

namespace ArgoTest\Serializer\Discriminator;

use Argo\AttributeCollector\Collection\AttributeCollection as CollectorAttributeCollection;
use Argo\AttributeCollector\Contract\AttributeManagerInterface;
use Argo\EntityDefinition\Collection\AttributeCollection;
use Argo\EntityDefinition\Reflector\ClassDefinition\ClassDefinitionReflectorInterface;
use Argo\EntityDefinition\TypeReflector\VariableTypeReflector;
use Argo\Serializer\Attribute\Discriminator as DiscriminatorResolverAttribute;
use Argo\Serializer\Attribute\DiscriminatorMap;
use Argo\Serializer\ContextFiller\VariableContextFillerInterface;
use Argo\Serializer\Contract\DenormalizerInterface;
use Argo\Serializer\Contract\DiscriminatorEnricherInterface;
use Argo\Serializer\Contract\DiscriminatorResolverInterface;
use Argo\Serializer\Discriminator\DiscriminatorResolver;
use Argo\Serializer\Exception\ValidationException;
use Argo\Serializer\Normalizer\ArrayNormalizer;
use Argo\Serializer\Normalizer\BuiltinDenormalizer;
use Argo\Serializer\Normalizer\ObjectNormalizer;
use Argo\Serializer\Serializer\Chain\ChainDenormalizer;
use Argo\Types\Atomic\ArrayType;
use Argo\Types\Atomic\BoolType;
use Argo\Types\Atomic\ClassType;
use Argo\Types\Atomic\FloatType;
use Argo\Types\Atomic\IntType;
use Argo\Types\Atomic\MixedType;
use Argo\Types\Atomic\ObjectType;
use Argo\Types\Atomic\StringType;
use Argo\Types\Complex\IntersectType;
use Argo\Types\Complex\UnionType;
use Argo\Types\NamedTypeInterface;
use Argo\Types\TypeInterface;
use ArgoTest\Serializer\Stubs\Bar;
use ArgoTest\Serializer\Stubs\Foo;
use ArgoTest\Serializer\Stubs\FooBarInterface;
use ArgoTest\Serializer\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(DiscriminatorResolver::class)]
class DiscriminatorResolverTest extends TestCase
{
    private DiscriminatorResolver $resolver;
    private DenormalizerInterface $denormalizer;

    public function setUp(): void
    {
        parent::setUp();

        $valueTypeReflector = new VariableTypeReflector();

        $attributes = [
            new DiscriminatorMap('field', ['foo' => Foo::class, 'bar' => Bar::class]),
        ];
        $attributeManager = \Mockery::mock(AttributeManagerInterface::class);
        $attributeManager->allows('getAttributesForClass')
            ->andReturn(new CollectorAttributeCollection($attributes));

        $this->resolver = new DiscriminatorResolver($valueTypeReflector, $attributeManager);
        $this->denormalizer = new ChainDenormalizer(
            new BuiltinDenormalizer(new VariableTypeReflector()),
            new ArrayNormalizer(),
            new ObjectNormalizer(
                \Mockery::mock(ClassDefinitionReflectorInterface::class),
                \Mockery::mock(DiscriminatorResolverInterface::class),
                \Mockery::mock(DiscriminatorEnricherInterface::class),
                \Mockery::mock(VariableContextFillerInterface::class),
            ),
        );
    }

    public static function casesProvider(): array
    {
        return [
            [new MixedType(), 'sdfdsf', [], new MixedType()],
            [new IntType(), 12, [], new IntType()],
            [new IntType(), 12.2, [], null],
            [new IntType(), 'str', [], null],
            [new FloatType(), 12, [], new FloatType()],
            [new FloatType(), 12.2, [], new FloatType()],
            [new FloatType(), 'str', [], null],
            [new StringType(), 12, [], new StringType()],
            [new StringType(), 12.2, [], new StringType()],
            [new StringType(), 'str', [], new StringType()],
            [new ObjectType(), (object) [], [], new ObjectType()],
            [new ObjectType(), [], [], new ObjectType()],
            [new ObjectType(), 12, [], null],
            [new ArrayType(), 12, [], null],
            [new ArrayType(), [], [], new ArrayType()],
            [new ArrayType(), (object) [], [], new ArrayType()],
            [new ClassType(Foo::class), 12, [], null],
            [new ClassType(Foo::class), (object) [], [], new ClassType(Foo::class)],
            [new ClassType(Foo::class), [], [], new ClassType(Foo::class)],
            [new IntersectType(new ObjectType()), 'test', [self::getDiscriminatorResolverAttribute()], new StringType()],
            [
                new UnionType(new ClassType(Foo::class), new ClassType(Bar::class)),
                ['field' => 'foo'],
                [self::getDiscriminatorMapAttribute()],
                new ClassType(Foo::class),
            ],
            [
                new UnionType(new ClassType(Foo::class), new ClassType(Bar::class)),
                ['field' => 'bar'],
                [self::getDiscriminatorMapAttribute()],
                new ClassType(Bar::class),
            ],
            [new ClassType(FooBarInterface::class), ['field' => 'foo'], [], new ClassType(Foo::class)],
            [new ClassType(FooBarInterface::class), ['field' => 'bar'], [], new ClassType(Bar::class)],
            [   // выбираем из всех типов строго совпадающий тип
                new UnionType(new BoolType(), new StringType(), new IntType()),
                12,
                [],
                new IntType(),
            ],
            [   // выбираем из всех подходящих типов - первый
                new UnionType(new BoolType(), new FloatType(), new StringType()),
                12,
                [],
                new FloatType(),
            ],
            [   // проверка discriminatorMap в Union
                new UnionType(new FloatType(), new ClassType(FooBarInterface::class), new StringType()),
                ['field' => 'bar'],
                [],
                new ClassType(Bar::class),
            ],
        ];
    }

    #[DataProvider('casesProvider')]
    public function testResolve(TypeInterface $targetType, mixed $value, array $attributes, ?NamedTypeInterface $expectedType)
    {
        if ($expectedType === null) {
            self::expectException(ValidationException::class);
        }

        $actualType = $this->resolver->resolve($targetType, $value, $this->denormalizer, new AttributeCollection($attributes));

        if ($expectedType !== null) {
            self::assertEquals($expectedType, $actualType);
        }
    }

    private static function getDiscriminatorResolverAttribute(): DiscriminatorResolverAttribute
    {
        $resolver = \Mockery::mock(DiscriminatorResolverInterface::class);
        $resolver->shouldReceive('resolve')
            ->andReturn(new StringType());

        return new DiscriminatorResolverAttribute($resolver);
    }

    private static function getDiscriminatorMapAttribute(): DiscriminatorMap
    {
        return new DiscriminatorMap('field', ['foo' => Foo::class, 'bar' => Bar::class]);
    }
}
