<?php

declare(strict_types=1);

namespace ArgoTest\Serializer\Normalizer;

use Argo\Serializer\Context\AttributesContext;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Context\Internal\PathContext;
use Argo\Serializer\Contract\DenormalizerInterface;
use Argo\Serializer\Contract\DiscriminatorResolverInterface;
use Argo\Serializer\Normalizer\UnionDenormalizer;
use Argo\Types\Atomic\ObjectType;
use Argo\Types\Atomic\StringType;
use Argo\Types\Complex\UnionType;
use ArgoTest\Serializer\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UnionDenormalizer::class)]
class UnionDenormalizerTest extends TestCase
{
    public function testDenormalize(): void
    {
        $expectedData = 'myData';
        $expectedType = new StringType();
        $inputType = new UnionType($expectedType, new ObjectType());
        $expectedFormat = 'xml';
        $expectedContext = new ContextBag();
        $expectedResult = 'result';

        $awareDenormalizer = \Mockery::mock(DenormalizerInterface::class);
        $awareDenormalizer->shouldReceive('denormalize')
            ->once()
            ->with($expectedData, $expectedType, $expectedFormat, $expectedContext)
            ->andReturn($expectedResult);

        $discriminatorResolver = \Mockery::mock(DiscriminatorResolverInterface::class);
        $discriminatorResolver->shouldReceive('resolve')
            ->once()
            ->with(
                $inputType,
                $expectedData,
                $awareDenormalizer,
                $expectedContext->get(AttributesContext::class)->attributeCollection,
                $expectedContext->get(PathContext::class),
            )
            ->andReturn($expectedType);

        $denormalizer = new UnionDenormalizer($discriminatorResolver);
        $denormalizer->setDenormalizer($awareDenormalizer);

        self::assertEquals(
            $expectedResult,
            $denormalizer->denormalize($expectedData, $inputType, $expectedFormat, $expectedContext),
        );
    }

    public function testSupportDenormalization(): void
    {
        $discriminatorResolver = \Mockery::mock(DiscriminatorResolverInterface::class);
        $denormalizer = new UnionDenormalizer($discriminatorResolver);

        self::assertFalse($denormalizer->supportsDenormalization('data', new StringType()));
        self::assertTrue($denormalizer->supportsDenormalization('data', new UnionType(new StringType(), new ObjectType())));
    }
}
