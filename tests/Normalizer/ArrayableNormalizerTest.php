<?php

declare(strict_types=1);

namespace ArgoTest\Serializer\Normalizer;

use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Contract\NormalizerInterface;
use Argo\Serializer\Normalizer\ArrayableNormalizer;
use ArgoTest\Serializer\TestCase;
use Hyperf\Contract\Arrayable as HyperfArrayable;
use Illuminate\Contracts\Support\Arrayable as LaravelArrayable;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ArrayableNormalizer::class)]
class ArrayableNormalizerTest extends TestCase
{
    public function testSupportsNormalization(): void
    {
        $normalizer = new ArrayableNormalizer();
        $this->assertFalse($normalizer->supportsNormalization([]));
        $this->assertTrue($normalizer->supportsNormalization(\Mockery::mock(HyperfArrayable::class)));
        $this->assertTrue($normalizer->supportsNormalization(\Mockery::mock(LaravelArrayable::class)));
    }

    public function testNormalizationHyperf(): void
    {
        $expected = ['normalized'];

        $normalizerMock = \Mockery::mock(NormalizerInterface::class);
        $normalizerMock->expects('normalize')
            ->once()
            ->with([], null, \Mockery::type(ContextBag::class))
            ->andReturn($expected);

        $normalizer = new ArrayableNormalizer();
        $normalizer->setNormalizer($normalizerMock);

        $data = \Mockery::mock(HyperfArrayable::class);
        $data->expects('toArray')->once()->andReturn([]);

        $this->assertEquals($expected, $normalizer->normalize($data));
    }

    public function testNormalizationLaravel(): void
    {
        $expected = ['normalized'];

        $normalizerMock = \Mockery::mock(NormalizerInterface::class);
        $normalizerMock->expects('normalize')
            ->once()
            ->with([], null, \Mockery::type(ContextBag::class))
            ->andReturn($expected);

        $normalizer = new ArrayableNormalizer();
        $normalizer->setNormalizer($normalizerMock);

        $data = \Mockery::mock(LaravelArrayable::class);
        $data->expects('toArray')->once()->andReturn([]);

        $this->assertEquals($expected, $normalizer->normalize($data));
    }
}
