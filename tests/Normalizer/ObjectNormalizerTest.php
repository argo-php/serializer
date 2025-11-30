<?php

declare(strict_types=1);

namespace ArgoTest\Serializer\Normalizer;

use Argo\AttributeCollector\AttributeManager;
use Argo\AttributeCollector\Collection\AttributeCollection as CollectorAttributeCollection;
use Argo\AttributeCollector\Contract\AttributeManagerInterface;
use Argo\DocBlockParser\Context\ContextFactory;
use Argo\DocBlockParser\Parser;
use Argo\DocBlockParser\PhpDocFactory;
use Argo\EntityDefinition\Collection\AttributeCollection;
use Argo\EntityDefinition\Reflector\ClassDefinition\ClassDefinitionReflector;
use Argo\EntityDefinition\Reflector\MethodDefinition\MethodDefinitionReflector;
use Argo\EntityDefinition\Reflector\ParameterDefinition\ParameterDefinitionReflector;
use Argo\EntityDefinition\Reflector\PropertyDefinition\PropertyDefinitionReflector;
use Argo\EntityDefinition\TypeReflector\TypeReflector;
use Argo\EntityDefinition\TypeReflector\VariableTypeReflector;
use Argo\Serializer\Attribute\Discriminator;
use Argo\Serializer\Attribute\DiscriminatorMap;
use Argo\Serializer\Attribute\Ignore;
use Argo\Serializer\Attribute\IgnoreIfEmpty;
use Argo\Serializer\Attribute\IgnoreIfNull;
use Argo\Serializer\Attribute\SerializedName;
use Argo\Serializer\Attribute\SerializedPath;
use Argo\Serializer\Context\AttributesContext;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Context\SerializationContext;
use Argo\Serializer\ContextFiller\AttributeContextFiller;
use Argo\Serializer\ContextFiller\VariableContextFiller;
use Argo\Serializer\Contract\DiscriminatorEnricherInterface;
use Argo\Serializer\Contract\DiscriminatorResolverInterface;
use Argo\Serializer\Contract\NormalizerInterface;
use Argo\Serializer\Discriminator\DiscriminatorEnricher;
use Argo\Serializer\Discriminator\DiscriminatorResolver;
use Argo\Serializer\Exception\CircularReferenceException;
use Argo\Serializer\Exception\InvalidArgumentException;
use Argo\Serializer\Exception\Validation\IncorrectTypeException;
use Argo\Serializer\Exception\Validation\RequiredException;
use Argo\Serializer\Exception\Validation\UnexpectedEnumValueException;
use Argo\Serializer\Exception\ValidationBagException;
use Argo\Serializer\Normalizer\ArrayNormalizer;
use Argo\Serializer\Normalizer\BuiltinDenormalizer;
use Argo\Serializer\Normalizer\ObjectNormalizer;
use Argo\Serializer\Serializer\Serializer;
use Argo\Types\Atomic\ClassType;
use Argo\Types\Atomic\IntType;
use ArgoTest\Serializer\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ObjectNormalizer::class)]
class ObjectNormalizerTest extends TestCase
{
    private function makeRealNormalizer(AttributeManagerInterface $attributeManager = new AttributeManager()): ObjectNormalizer
    {
        $typeReflector = new TypeReflector();
        $contextFactory = new ContextFactory();
        $parser = new Parser();
        $phpDocFactory = new PhpDocFactory($parser, $contextFactory);
        $parameterReflector = new ParameterDefinitionReflector($typeReflector);
        $methodReflector = new MethodDefinitionReflector($phpDocFactory, $typeReflector, $parameterReflector);
        $propertyReflector = new PropertyDefinitionReflector($phpDocFactory, $typeReflector);
        $variableTypeReflector = new VariableTypeReflector();

        return new ObjectNormalizer(
            new ClassDefinitionReflector(
                $phpDocFactory,
                $methodReflector,
                $propertyReflector,
            ),
            new DiscriminatorResolver($variableTypeReflector, $attributeManager),
            new DiscriminatorEnricher($attributeManager),
            new VariableContextFiller(new AttributeContextFiller()),
        );
    }

    public function testSupportsDenormalization(): void
    {
        $denormalizer = $this->makeRealNormalizer();
        self::assertTrue($denormalizer->supportsDenormalization('', new ClassType(self::class)));
        self::assertFalse($denormalizer->supportsDenormalization('', new IntType()));
        self::assertFalse($denormalizer->supportsDenormalization('', new ClassType('unknownClass12e3')));
    }

    public function testDenormalizeObjectWithProperties(): void
    {
        $serializer = new Serializer([new ArrayNormalizer(), $this->makeRealNormalizer(), new BuiltinDenormalizer(new VariableTypeReflector())]);

        $data = [
            'integer' => 123,
            'string' => 'string',
            'float' => 12.30,
            'array' => [
                'hello',
                'foo' => 'bar',
            ],
            'class' => [
                'foo' => 'fooValue',
                'bar' => 'barValue',
            ],
            'object' => [
                'test' => 'testValue',
                'another' => 123,
            ],
            'protected' => 'expectedProtected',
            'private' => 'expectedPrivate',
        ];
        $expected = new ObjectWithPropertiesStub();
        $expected->integer = 123;
        $expected->string = 'string';
        $expected->float = 12.3;
        $expected->array = [
            'hello',
            'foo' => 'bar',
        ];
        $expected->class = new SimpleObjectStub('fooValue', 'barValue');
        $expected->object = (object) [
            'test' => 'testValue',
            'another' => 123,
        ];

        $result = $serializer->denormalize($data, new ClassType(ObjectWithPropertiesStub::class), 'json');

        self::assertEquals($expected, $result);
    }

    public function testDenormalizeObjectWithPromotedConstructor(): void
    {
        $serializer = new Serializer([new ArrayNormalizer(), $this->makeRealNormalizer(), new BuiltinDenormalizer(new VariableTypeReflector())]);

        $data = [
            'integer' => 123,
            'string' => 'string',
            'float' => 12.30,
            'array' => [
                'hello',
                'foo' => 'bar',
            ],
            'class' => [
                'foo' => 'fooValue',
                'bar' => 'barValue',
            ],
            'object' => [
                'test' => 'testValue',
                'another' => 123,
            ],
            'protected' => 'expectedProtected',
            'private' => 'expectedPrivate',
        ];

        $expected = new ObjectWithPromotedConstructorStub(
            123,
            'string',
            12.3,
            [
                'hello',
                'foo' => 'bar',
            ],
            new SimpleObjectStub('fooValue', 'barValue'),
            (object) [
                'test' => 'testValue',
                'another' => 123,
            ],
            'expectedProtected',
            'expectedPrivate',
        );

        $result = $serializer->denormalize($data, new ClassType(ObjectWithPromotedConstructorStub::class), 'json');

        self::assertEquals($expected, $result);
    }

    public function testDenormalizeObjectWithConstructor(): void
    {
        $serializer = new Serializer([new ArrayNormalizer(), $this->makeRealNormalizer(), new BuiltinDenormalizer(new VariableTypeReflector())]);

        $data = [
            'integer' => 123,
            'string' => 'string',
            'float' => 12.30,
            'array' => [
                'hello',
                'foo' => 'bar',
            ],
            'class' => [
                'foo' => 'fooValue',
                'bar' => 'barValue',
            ],
            'object' => [
                'test' => 'testValue',
                'another' => 123,
            ],
            'protected' => 'expectedProtected',
            'private' => 'expectedPrivate',
        ];

        $expected = new ObjectWithConstructorStub(
            123,
            'string',
            12.3,
            [
                'hello',
                'foo' => 'bar',
            ],
            new SimpleObjectStub('fooValue', 'barValue'),
            (object) [
                'test' => 'testValue',
                'another' => 123,
            ],
            'expectedProtected',
            'expectedPrivate',
        );

        $result = $serializer->denormalize($data, new ClassType(ObjectWithConstructorStub::class), 'json');

        self::assertEquals($expected, $result);
    }

    public function testDenormalizeObjectWithPropertiesAndConstructor(): void
    {
        $serializer = new Serializer([new ArrayNormalizer(), $this->makeRealNormalizer(), new BuiltinDenormalizer(new VariableTypeReflector())]);

        $data = [
            'integer' => 123,
            'string' => 'string',
            'float' => 12.30,
            'array' => [
                'hello',
                'foo' => 'bar',
            ],
            'class' => [
                'foo' => 'fooValue',
                'bar' => 'barValue',
            ],
            'object' => [
                'test' => 'testValue',
                'another' => 123,
            ],
            'protected' => 'expectedProtected',
            'private' => 'expectedPrivate',
        ];
        $expected = new ObjectWithPropertiesAndConstructorStub(
            123,
            'expectedPrivate',
        );
        $expected->string = 'string';
        $expected->float = 12.3;
        $expected->array = [
            'hello',
            'foo' => 'bar',
        ];
        $expected->class = new SimpleObjectStub('fooValue', 'barValue');
        $expected->object = (object) [
            'test' => 'testValue',
            'another' => 123,
        ];

        $result = $serializer->denormalize($data, new ClassType(ObjectWithPropertiesAndConstructorStub::class), 'json');

        self::assertEquals($expected, $result);
    }

    public function testDenormalizeDefaultValue(): void
    {
        $serializer = new Serializer([$this->makeRealNormalizer(), new BuiltinDenormalizer(new VariableTypeReflector())]);
        $data = (object) [
            'foo' => 'anotherFoo',
        ];

        $result = $serializer->denormalize($data, new ClassType(ObjectWithDefaultStub::class), 'json');
        self::assertEquals('anotherFoo', $result->foo);
        self::assertEquals('defaultBar', $result->bar);
    }

    public function testDenormalizeRequiredValue(): void
    {
        $serializer = new Serializer([$this->makeRealNormalizer(), new BuiltinDenormalizer(new VariableTypeReflector())]);
        $data = [
            'foo' => 'anotherFoo',
        ];

        self::expectException(ValidationBagException::class);
        $serializer->denormalize($data, new ClassType(SimpleObjectStub::class), 'json');
    }

    public function testDenormalizeInvalidArgumentException()
    {
        $normalizer = $this->makeRealNormalizer();

        self::expectException(InvalidArgumentException::class);
        $normalizer->denormalize('', new IntType());

        self::expectException(InvalidArgumentException::class);
        $normalizer->denormalize('', new ClassType('UnknownClassFooBar_s242d'));
    }

    public function testDenormalizeIncorrectTypeException()
    {
        $normalizer = $this->makeRealNormalizer();

        self::expectException(IncorrectTypeException::class);
        $normalizer->denormalize('', new ClassType(self::class));
    }

    public function testDenormalizeReadOnlyClass()
    {
        $serializer = new Serializer([$this->makeRealNormalizer(), new BuiltinDenormalizer(new VariableTypeReflector())]);

        $data = ['foo' => 'fooValue', 'bar' => 'barValue'];
        $result = $serializer->denormalize($data, new ClassType(ReadOnlyObjectStub::class));
        self::assertEquals('fooValue', $result->foo);
        self::assertEquals('defaultBar', $result->bar);
    }

    public function testDenormalizeReadOnlyProperty()
    {
        $serializer = new Serializer([$this->makeRealNormalizer(), new BuiltinDenormalizer(new VariableTypeReflector())]);

        $data = ['foo' => 'fooValue', 'bar' => 'barValue'];
        $result = $serializer->denormalize($data, new ClassType(ReadOnlyPropertyObjectStub::class));
        self::assertEquals('defaultFoo', $result->foo);
        self::assertEquals('barValue', $result->bar);
    }

    public function testDenormalizeXmlProperty(): void
    {
        $serializer = new Serializer([$this->makeRealNormalizer(), new BuiltinDenormalizer(new VariableTypeReflector())]);
        $data = ['foo' => 'fooValue', 'bar' => 'barValue'];
        $result = $serializer->denormalize($data, new ClassType(SimpleObjectWithPropertiesStub::class), 'xml');
        self::assertEquals('fooValue', $result->foo);
        self::assertEquals('barValue', $result->bar);
    }

    public function testDenormalizeXmlParameter(): void
    {
        $serializer = new Serializer([$this->makeRealNormalizer(), new BuiltinDenormalizer(new VariableTypeReflector())]);
        $data = ['foo' => 'fooValue', 'bar' => 'barValue'];
        $result = $serializer->denormalize($data, new ClassType(SimpleObjectStub::class), 'xml');
        self::assertEquals('fooValue', $result->foo);
        self::assertEquals('barValue', $result->bar);
    }

    public function testDenormalizeCustomName(): void
    {
        $serializer = new Serializer([$this->makeRealNormalizer(), new BuiltinDenormalizer(new VariableTypeReflector())]);
        $data = ['hello' => 'fooValue', 'bar' => 'barValue'];
        $result = $serializer->denormalize($data, new ClassType(CustomPropertyNameStub::class));
        self::assertEquals('fooValue', $result->foo);
        self::assertEquals('barValue', $result->bar);
    }

    public function testDenormalizeCustomPath(): void
    {
        $serializer = new Serializer([$this->makeRealNormalizer(), new BuiltinDenormalizer(new VariableTypeReflector())]);
        $data = ['hello' => ['foo' => ['bar' => 'fooValue']], 'bar' => 'barValue'];
        $result = $serializer->denormalize($data, new ClassType(CustomPropertyPathStub::class));
        self::assertEquals('fooValue', $result->foo);
        self::assertEquals('barValue', $result->bar);
    }

    public function testDenormalizeWithIgnore(): void
    {
        $serializer = new Serializer([$this->makeRealNormalizer(), new BuiltinDenormalizer(new VariableTypeReflector())]);
        $data = ['foo' => 'fooValue', 'bar' => 'barValue'];

        $result = $serializer->denormalize($data, new ClassType(ObjectWithIgnore::class));
        self::assertEquals('fooValue', $result->foo);
        self::assertEquals('defaultBar', $result->bar);
    }

    public function testDenormalizeWithIgnoreIfNull(): void
    {
        $serializer = new Serializer([$this->makeRealNormalizer(), new BuiltinDenormalizer(new VariableTypeReflector())]);

        $data = ['foo' => 'fooValue', 'bar' => 'barValue'];
        $result = $serializer->denormalize($data, new ClassType(ObjectWithIgnoreIfNull::class));
        self::assertEquals('fooValue', $result->foo);
        self::assertEquals('barValue', $result->bar);

        $data = ['foo' => 'fooValue', 'bar' => ''];
        $result = $serializer->denormalize($data, new ClassType(ObjectWithIgnoreIfNull::class));
        self::assertEquals('fooValue', $result->foo);
        self::assertEquals('', $result->bar);

        $data = ['foo' => 'fooValue', 'bar' => null];
        $result = $serializer->denormalize($data, new ClassType(ObjectWithIgnoreIfNull::class));
        self::assertEquals('fooValue', $result->foo);
        self::assertEquals('defaultBar', $result->bar);
    }

    public function testDenormalizeWithIgnoreIfEmpty(): void
    {
        $serializer = new Serializer([$this->makeRealNormalizer(), new BuiltinDenormalizer(new VariableTypeReflector())]);

        $data = ['foo' => 'fooValue', 'bar' => 'barValue'];
        $result = $serializer->denormalize($data, new ClassType(ObjectWithIgnoreIfEmpty::class));
        self::assertEquals('fooValue', $result->foo);
        self::assertEquals('barValue', $result->bar);

        $data = ['foo' => 'fooValue', 'bar' => ''];
        $result = $serializer->denormalize($data, new ClassType(ObjectWithIgnoreIfEmpty::class));
        self::assertEquals('fooValue', $result->foo);
        self::assertEquals('defaultBar', $result->bar);

        $data = ['foo' => 'fooValue', 'bar' => null];
        $result = $serializer->denormalize($data, new ClassType(ObjectWithIgnoreIfEmpty::class));
        self::assertEquals('fooValue', $result->foo);
        self::assertEquals('defaultBar', $result->bar);
    }

    public function testDenormalizeDiscriminatorInterface(): void
    {
        $attributes = [
            new DiscriminatorMap('type', [
                'foo' => DiscriminatedFooClass::class,
                'bar' => DiscriminatedBarClass::class,
            ], DiscriminatedFooClass::class),
        ];
        $attributeManager = \Mockery::mock(AttributeManagerInterface::class);
        $attributeManager->allows('getAttributesForClass')
            ->with(DiscriminatedObjectInterface::class)
            ->andReturn(new CollectorAttributeCollection($attributes));
        $attributeManager->allows('getAttributesForClass')
            ->andReturn(new CollectorAttributeCollection());

        $serializer = new Serializer([$this->makeRealNormalizer($attributeManager), new BuiltinDenormalizer(new VariableTypeReflector())]);

        $data = ['type' => 'foo', 'value' => 'hello'];
        $result = $serializer->denormalize($data, new ClassType(DiscriminatedObjectInterface::class));
        self::assertInstanceOf(DiscriminatedFooClass::class, $result);
        self::assertEquals('hello', $result->value);

        $data = ['value' => 'hello'];
        $result = $serializer->denormalize($data, new ClassType(DiscriminatedObjectInterface::class));
        self::assertInstanceOf(DiscriminatedFooClass::class, $result);
        self::assertEquals('hello', $result->value);

        $data = ['type' => 'bar', 'value' => 'hello'];
        $result = $serializer->denormalize($data, new ClassType(DiscriminatedObjectInterface::class));
        self::assertInstanceOf(DiscriminatedBarClass::class, $result);
        self::assertEquals('hello', $result->value);

        $data = ['type' => 'incorrect', 'value' => 'hello'];
        $result = $serializer->denormalize($data, new ClassType(DiscriminatedObjectInterface::class));
        self::assertInstanceOf(DiscriminatedFooClass::class, $result);
        self::assertEquals('hello', $result->value);
    }

    public function testDenormalizeDiscriminatorInterfaceRequiredException(): void
    {
        $attributes = [
            new DiscriminatorMap('type', [
                'foo' => DiscriminatedFooClass::class,
                'bar' => DiscriminatedBarClass::class,
            ]),
        ];
        $attributeManager = \Mockery::mock(AttributeManagerInterface::class);
        $attributeManager->allows('getAttributesForClass')
            ->with(DiscriminatedObjectInterface::class)
            ->andReturn(new CollectorAttributeCollection($attributes));
        $attributeManager->allows('getAttributesForClass')
            ->andReturn(new CollectorAttributeCollection());

        $serializer = new Serializer([$this->makeRealNormalizer($attributeManager), new BuiltinDenormalizer(new VariableTypeReflector())]);

        $this->expectException(RequiredException::class);
        $data = ['value' => 'hello'];
        $serializer->denormalize($data, new ClassType(DiscriminatedObjectInterface::class));
    }

    public function testDenormalizeDiscriminatorInterfaceIncorrectException(): void
    {
        $attributes = [
            new DiscriminatorMap('type', [
                'foo' => DiscriminatedFooClass::class,
                'bar' => DiscriminatedBarClass::class,
            ]),
        ];
        $attributeManager = \Mockery::mock(AttributeManagerInterface::class);
        $attributeManager->allows('getAttributesForClass')
            ->with(DiscriminatedObjectInterface::class)
            ->andReturn(new CollectorAttributeCollection($attributes));
        $attributeManager->allows('getAttributesForClass')
            ->andReturn(new CollectorAttributeCollection());

        $serializer = new Serializer([$this->makeRealNormalizer($attributeManager), new BuiltinDenormalizer(new VariableTypeReflector())]);

        $this->expectException(UnexpectedEnumValueException::class);
        $data = ['type' => 'incorrect', 'value' => 'hello'];
        $serializer->denormalize($data, new ClassType(DiscriminatedObjectInterface::class));
    }

    public function testDenormalizeDiscriminatorAbstract(): void
    {
        $attributes = [
            new DiscriminatorMap('type', [
                'foo' => DiscriminatedFooClass::class,
                'bar' => DiscriminatedBarClass::class,
            ]),
        ];
        $attributeManager = \Mockery::mock(AttributeManagerInterface::class);
        $attributeManager->allows('getAttributesForClass')
            ->with(DiscriminatedObjectAbstract::class)
            ->andReturn(new CollectorAttributeCollection($attributes));
        $attributeManager->allows('getAttributesForClass')
            ->andReturn(new CollectorAttributeCollection());

        $serializer = new Serializer([$this->makeRealNormalizer($attributeManager), new BuiltinDenormalizer(new VariableTypeReflector())]);

        $data = ['type' => 'foo', 'value' => 'hello'];
        $result = $serializer->denormalize($data, new ClassType(DiscriminatedObjectAbstract::class));
        self::assertInstanceOf(DiscriminatedFooClass::class, $result);
        self::assertEquals('hello', $result->value);

        $this->expectException(RequiredException::class);
        $data = ['value' => 'hello'];
        $serializer->denormalize($data, new ClassType(DiscriminatedObjectAbstract::class));

        $data = ['type' => 'bar', 'value' => 'hello'];
        $result = $serializer->denormalize($data, new ClassType(DiscriminatedObjectAbstract::class));
        self::assertInstanceOf(DiscriminatedBarClass::class, $result);
        self::assertEquals('hello', $result->value);

        $this->expectException(UnexpectedEnumValueException::class);
        $data = ['type' => 'incorrect', 'value' => 'hello'];
        $serializer->denormalize($data, new ClassType(DiscriminatedObjectAbstract::class));
    }

    public function testDenormalizeCustomDiscriminator(): void
    {
        $serializer = new Serializer([$this->makeRealNormalizer(), new BuiltinDenormalizer(new VariableTypeReflector())]);
        $discriminatorResolver = \Mockery::mock(DiscriminatorResolverInterface::class);
        $discriminatorResolver->shouldReceive('resolve')
            ->andReturn(new ClassType(DiscriminatedFooClass::class));

        $data = ['value' => 'hello'];
        $context = new ContextBag(
            new AttributesContext(
                new AttributeCollection(
                    [
                        new Discriminator($discriminatorResolver),
                    ],
                ),
            ),
        );

        $result = $serializer->denormalize(
            $data,
            new ClassType(DiscriminatedObjectInterface::class),
            contextBag: $context,
        );
        self::assertInstanceOf(DiscriminatedFooClass::class, $result);
        self::assertEquals('hello', $result->value);
    }

    public function testSupportsNormalization(): void
    {
        $normalizer = $this->makeRealNormalizer();
        self::assertTrue($normalizer->supportsNormalization(new SimpleObjectStub('123', '123')));
        self::assertTrue($normalizer->supportsNormalization((object) ['foo' => 133, 'bar' => 'barValue']));
        self::assertFalse($normalizer->supportsNormalization('string'));
    }

    public function testNormalize(): void
    {
        $serializer = new Serializer([new ArrayNormalizer(), $this->makeRealNormalizer(), new BuiltinDenormalizer(new VariableTypeReflector())]);
        $object = new ObjectWithConstructorStub(
            123,
            'string',
            12.3,
            [
                'hello',
                'foo' => 'bar',
            ],
            new SimpleObjectStub('fooValue', 'barValue'),
            (object) [
                'test' => 'testValue',
                'another' => 123,
            ],
            'expectedProtected',
            'expectedPrivate',
        );

        $expected = (object) [
            'integer' => 123,
            'string' => 'string',
            'float' => 12.3,
            'array' => [
                'hello',
                'foo' => 'bar',
            ],
            'class' => (object) [
                'foo' => 'fooValue',
                'bar' => 'barValue',
            ],
            'object' => (object) [
                'test' => 'testValue',
                'another' => 123,
            ],
        ];

        self::assertEquals($expected, $serializer->normalize($object));
    }

    public function testNormalizeStdClass(): void
    {
        $mockNormalizer = \Mockery::mock(NormalizerInterface::class);
        $mockNormalizer->shouldReceive('normalize')
            ->twice()
            ->andReturnArg(0);

        $normalizer = $this->makeRealNormalizer();
        $normalizer->setNormalizer($mockNormalizer);

        $object = (object) [
            'foo' => 'fooValue',
            'bar' => 'barValue',
        ];

        $expected = (object) [
            'foo' => 'fooValue',
            'bar' => 'barValue',
        ];

        self::assertEquals($expected, $normalizer->normalize($object));
    }

    public function testNormalizeObject(): void
    {
        $mockNormalizer = \Mockery::mock(NormalizerInterface::class);
        $mockNormalizer->shouldReceive('normalize')
            ->twice()
            ->andReturnArg(0);

        $normalizer = $this->makeRealNormalizer();
        $normalizer->setNormalizer($mockNormalizer);

        $object = new SimpleObjectStub('fooValue', 'barValue');

        $expected = (object) [
            'foo' => 'fooValue',
            'bar' => 'barValue',
        ];

        self::assertEquals($expected, $normalizer->normalize($object));
    }

    public function testNormalizeWithCustomName(): void
    {
        $mockNormalizer = \Mockery::mock(NormalizerInterface::class);
        $mockNormalizer->shouldReceive('normalize')
            ->twice()
            ->andReturnArg(0);

        $normalizer = $this->makeRealNormalizer();
        $normalizer->setNormalizer($mockNormalizer);

        $object = new CustomPropertyNameStub('fooValue', 'barValue');

        $expected = (object) [
            'hello' => 'fooValue',
            'bar' => 'barValue',
        ];

        self::assertEquals($expected, $normalizer->normalize($object));
    }

    public function testNormalizeWithCustomPath(): void
    {
        $mockNormalizer = \Mockery::mock(NormalizerInterface::class);
        $mockNormalizer->shouldReceive('normalize')
            ->twice()
            ->andReturnArg(0);

        $normalizer = $this->makeRealNormalizer();
        $normalizer->setNormalizer($mockNormalizer);

        $object = new CustomPropertyPathStub('fooValue', 'barValue');

        $expected = (object) [
            'hello' => ['foo' => ['bar' => 'fooValue']],
            'bar' => 'barValue',
        ];

        self::assertEquals($expected, $normalizer->normalize($object));
    }

    public function testNormalizeWithXmlProperty(): void
    {
        $mockNormalizer = \Mockery::mock(NormalizerInterface::class);
        $mockNormalizer->shouldReceive('normalize')
            ->twice()
            ->andReturnArg(0);

        $normalizer = $this->makeRealNormalizer();
        $normalizer->setNormalizer($mockNormalizer);

        $object = new SimpleObjectStub('fooValue', 'barValue');

        $expected = (object) [
            'foo' => 'fooValue',
            'bar' => 'barValue',
        ];

        self::assertEquals($expected, $normalizer->normalize($object, 'xml'));
    }

    public function testNormalizeAsArray(): void
    {
        $mockNormalizer = \Mockery::mock(NormalizerInterface::class);
        $mockNormalizer->shouldReceive('normalize')
            ->twice()
            ->andReturnArg(0);

        $normalizer = $this->makeRealNormalizer();
        $normalizer->setNormalizer($mockNormalizer);

        $object = new SimpleObjectStub('fooValue', 'barValue');

        $expected = [
            'foo' => 'fooValue',
            'bar' => 'barValue',
        ];

        $context = new ContextBag(new SerializationContext(normalizeAsArray: true));

        self::assertEquals($expected, $normalizer->normalize($object, contextBag: $context));
    }

    public function testNormalizeWithDepth(): void
    {
        $serializer = new Serializer([new ArrayNormalizer(), $this->makeRealNormalizer(), new BuiltinDenormalizer(new VariableTypeReflector())]);

        $object = (object) [
            'foo' => (object) [
                'fooInner' => 'fooValueInner',
                'barInner' => (object) [
                    'fooInnerInner' => 'fooValueInnerInner',
                ],
            ],
            'bar' => 'barValue',
        ];

        $expectedDepth1 = (object) [
            'foo' => (object) [
                'fooInner' => null,
                'barInner' => null,
            ],
            'bar' => 'barValue',
        ];

        $expectedDepth2 = (object) [
            'foo' => (object) [
                'fooInner' => 'fooValueInner',
                'barInner' => (object) [
                    'fooInnerInner' => null,
                ],
            ],
            'bar' => 'barValue',
        ];

        $context = new ContextBag(new SerializationContext(serializationDepth: 1));
        self::assertEquals($expectedDepth1, $serializer->normalize($object, contextBag: $context));

        $context = new ContextBag(new SerializationContext(serializationDepth: 2));
        self::assertEquals($expectedDepth2, $serializer->normalize($object, contextBag: $context));
    }

    public function testNormalizeWithCircularReferences(): void
    {
        $serializer = new Serializer([new ArrayNormalizer(), $this->makeRealNormalizer(), new BuiltinDenormalizer(new VariableTypeReflector())]);

        $object = (object) [
            'foo' => null,
            'bar' => 'hello',
        ];
        $object->foo = $object;

        $expected = (object) [
            'foo' => null,
            'bar' => 'hello',
        ];

        $context = new ContextBag(new SerializationContext(circularReferenceLimit: 1, throwOnCircularReference: false));
        self::assertEquals($expected, $serializer->normalize($object, contextBag: $context));

        $expected = (object) [
            'foo' => (object) [
                'foo' => null,
                'bar' => 'hello',
            ],
            'bar' => 'hello',
        ];

        $context = new ContextBag(new SerializationContext(circularReferenceLimit: 2, throwOnCircularReference: false));
        self::assertEquals($expected, $serializer->normalize($object, contextBag: $context));

        $this->expectException(CircularReferenceException::class);
        self::assertEquals($expected, $serializer->normalize($object));
    }

    public function testNormalizeWithDiscriminatorMapInterface(): void
    {
        $attributes = [
            new DiscriminatorMap('type', [
                'foo' => DiscriminatedFooClass::class,
                'bar' => DiscriminatedBarClass::class,
            ]),
        ];
        $attributeManager = \Mockery::mock(AttributeManagerInterface::class);
        $attributeManager->allows('getAttributesForClass')
            ->with(DiscriminatedObjectInterface::class)
            ->andReturn(new CollectorAttributeCollection($attributes));
        $attributeManager->allows('getAttributesForClass')
            ->andReturn(new CollectorAttributeCollection());

        $serializer = new Serializer([new ArrayNormalizer(), $this->makeRealNormalizer($attributeManager), new BuiltinDenormalizer(new VariableTypeReflector())]);
        $object = new DiscriminatedFooClass();
        $object->value = 'hello';
        $expected = (object) [
            'type' => 'foo',
            'value' => 'hello',
        ];

        self::assertEquals($expected, $serializer->normalize($object));
    }

    public function testNormalizeWithDiscriminatorMapAbstract(): void
    {
        $attributes = [
            new DiscriminatorMap('type', [
                'foo' => DiscriminatedFooClass::class,
                'bar' => DiscriminatedBarClass::class,
            ]),
        ];
        $attributeManager = \Mockery::mock(AttributeManagerInterface::class);
        $attributeManager->allows('getAttributesForClass')
            ->with(DiscriminatedObjectAbstract::class)
            ->andReturn(new CollectorAttributeCollection($attributes));
        $attributeManager->allows('getAttributesForClass')
            ->andReturn(new CollectorAttributeCollection());

        $serializer = new Serializer([new ArrayNormalizer(), $this->makeRealNormalizer($attributeManager), new BuiltinDenormalizer(new VariableTypeReflector())]);
        $object = new DiscriminatedFooClass();
        $object->value = 'hello';
        $expected = (object) [
            'type' => 'foo',
            'value' => 'hello',
        ];

        self::assertEquals($expected, $serializer->normalize($object));
    }

    public function testNormalizeWithDiscriminatorResolver(): void
    {
        $expected = [
            'types' => 'fooooo',
            'value' => 'hello',
        ];

        $mock = \Mockery::mock(DiscriminatorEnricherInterface::class);
        $mock->shouldReceive('enrich')
            ->andReturn($expected);

        $serializer = new Serializer([new ArrayNormalizer(), $this->makeRealNormalizer(), new BuiltinDenormalizer(new VariableTypeReflector())]);
        $object = new DiscriminatedFooClass();
        $object->value = 'hello';

        $context = new ContextBag(
            new AttributesContext(
                new AttributeCollection([
                    new Discriminator(discriminatorEnricher: $mock),
                ]),
            ),
        );

        self::assertSame($expected, (array) $serializer->normalize($object, contextBag: $context));
    }

    public function testNormalizeWithIgnore(): void
    {
        $mockNormalizer = \Mockery::mock(NormalizerInterface::class);
        $mockNormalizer->shouldReceive('normalize')
            ->andReturnArg(0);

        $normalizer = $this->makeRealNormalizer();
        $normalizer->setNormalizer($mockNormalizer);

        $object = new ObjectWithIgnore('fooValue', 'barValue');
        $expected = ['foo' => 'fooValue'];
        self::assertSame($expected, (array) $normalizer->normalize($object));

        $object = new ObjectWithIgnore('', '');
        $expected = ['foo' => ''];
        self::assertSame($expected, (array) $normalizer->normalize($object));

        $object = new ObjectWithIgnore(null, null);
        $expected = ['foo' => null];
        self::assertSame($expected, (array) $normalizer->normalize($object));
    }

    public function testNormalizeWithIgnoreIfNull(): void
    {
        $mockNormalizer = \Mockery::mock(NormalizerInterface::class);
        $mockNormalizer->shouldReceive('normalize')
            ->andReturnArg(0);

        $normalizer = $this->makeRealNormalizer();
        $normalizer->setNormalizer($mockNormalizer);

        $object = new ObjectWithIgnoreIfNull('fooValue', 'barValue');
        $expected = ['foo' => 'fooValue', 'bar' => 'barValue'];
        self::assertSame($expected, (array) $normalizer->normalize($object));

        $object = new ObjectWithIgnoreIfNull('', '');
        $expected = ['foo' => '', 'bar' => ''];
        self::assertSame($expected, (array) $normalizer->normalize($object));

        $object = new ObjectWithIgnoreIfNull(null, null);
        $expected = ['foo' => null];
        self::assertSame($expected, (array) $normalizer->normalize($object));
    }

    public function testNormalizeWithIgnoreIfEmpty(): void
    {
        $mockNormalizer = \Mockery::mock(NormalizerInterface::class);
        $mockNormalizer->shouldReceive('normalize')
            ->andReturnArg(0);

        $normalizer = $this->makeRealNormalizer();
        $normalizer->setNormalizer($mockNormalizer);

        $object = new ObjectWithIgnoreIfEmpty('fooValue', 'barValue');
        $expected = ['foo' => 'fooValue', 'bar' => 'barValue'];
        self::assertSame($expected, (array) $normalizer->normalize($object));

        $object = new ObjectWithIgnoreIfEmpty('', '');
        $expected = ['foo' => ''];
        self::assertSame($expected, (array) $normalizer->normalize($object));

        $object = new ObjectWithIgnoreIfEmpty(null, null);
        $expected = ['foo' => null];
        self::assertSame($expected, (array) $normalizer->normalize($object));
    }
}

readonly class ReadOnlyObjectStub
{
    public string $foo;
    public string $bar;

    public function __construct(string $foo)
    {
        $this->foo = $foo;
        $this->bar = 'defaultBar';
    }
}

class ReadOnlyPropertyObjectStub
{
    public readonly string $foo;
    public string $bar;

    public function __construct()
    {
        $this->foo = 'defaultFoo';
    }
}

class SimpleObjectWithPropertiesStub
{
    public string $foo;
    public string $bar;
}

class SimpleObjectStub
{
    public function __construct(
        public string $foo,
        public string $bar,
    ) {}
}

class CustomPropertyNameStub
{
    public function __construct(
        #[SerializedName('hello')]
        public string $foo,
        public string $bar,
    ) {}
}

class CustomPropertyPathStub
{
    public function __construct(
        #[SerializedPath('hello.foo.bar')]
        public string $foo,
        public string $bar,
    ) {}
}

class ObjectWithIgnore
{
    public function __construct(
        public ?string $foo = 'defaultFoo',
        #[Ignore]
        public ?string $bar = 'defaultBar',
    ) {}
}

class ObjectWithIgnoreIfNull
{
    public function __construct(
        public ?string $foo = 'defaultFoo',
        #[IgnoreIfNull]
        public ?string $bar = 'defaultBar',
    ) {}
}

class ObjectWithIgnoreIfEmpty
{
    public function __construct(
        public ?string $foo = 'defaultFoo',
        #[IgnoreIfEmpty]
        public ?string $bar = 'defaultBar',
    ) {}
}

class ObjectWithDefaultStub
{
    public function __construct(
        public string $foo = 'defaultFoo',
        public string $bar = 'defaultBar',
    ) {}
}

class ObjectWithPropertiesStub
{
    public int $integer;
    public string $string;
    public float $float;
    public array $array;
    public SimpleObjectStub $class;
    public object $object;

    protected string $protected = 'protected';
    private string $private = 'private';
}

class ObjectWithPromotedConstructorStub
{
    public function __construct(
        public int $integer,
        public string $string,
        public float $float,
        public array $array,
        public SimpleObjectStub $class,
        public object $object,
        protected string $protected = 'protected',
        private string $private = 'private',
    ) {}
}

class ObjectWithConstructorStub
{
    public int $integer;
    public string $string;
    public float $float;
    public array $array;
    public SimpleObjectStub $class;
    public object $object;

    protected string $protected;
    private string $private;

    public function __construct(
        int $integer,
        string $string,
        float $float,
        array $array,
        SimpleObjectStub $class,
        object $object,
        string $protected = 'protected',
        string $private = 'private',
    ) {
        $this->integer = $integer;
        $this->string = $string;
        $this->float = $float;
        $this->array = $array;
        $this->class = $class;
        $this->object = $object;
        $this->protected = $protected;
        $this->private = $private;
    }
}

class ObjectWithPropertiesAndConstructorStub
{
    public readonly int $integer;
    public string $string;
    public float $float;
    public array $array;
    public SimpleObjectStub $class;
    public object $object;

    protected string $protected;
    private string $private;

    public function __construct(
        int $integer,
        string $private = 'private',
    ) {
        $this->integer = $integer + 1;
        $this->private = $private;
    }
}

interface DiscriminatedObjectInterface {}

abstract class DiscriminatedObjectAbstract {}

class DiscriminatedFooClass extends DiscriminatedObjectAbstract implements DiscriminatedObjectInterface
{
    public string $value;
}

class DiscriminatedBarClass extends DiscriminatedObjectAbstract implements DiscriminatedObjectInterface
{
    public string $value;
}
