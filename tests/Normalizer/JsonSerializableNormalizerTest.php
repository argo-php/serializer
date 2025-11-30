<?php

declare(strict_types=1);

namespace ArgoTest\Serializer\Normalizer;

use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Contract\NormalizerInterface;
use Argo\Serializer\Normalizer\JsonSerializableNormalizer;
use ArgoTest\Serializer\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(JsonSerializableNormalizer::class)]
class JsonSerializableNormalizerTest extends TestCase
{
    public function testSupportsNormalization()
    {
        $normalizer = new JsonSerializableNormalizer();
        $this->assertFalse($normalizer->supportsNormalization([]));
        $this->assertTrue($normalizer->supportsNormalization(\Mockery::mock(\JsonSerializable::class)));
    }

    public function testNormalization()
    {
        $expected = ['normalized'];

        $normalizerMock = \Mockery::mock(NormalizerInterface::class);
        $normalizerMock->expects('normalize')
            ->once()
            ->with([], null, \Mockery::type(ContextBag::class))
            ->andReturn($expected);

        $normalizer = new JsonSerializableNormalizer();
        $normalizer->setNormalizer($normalizerMock);

        $data = \Mockery::mock(\JsonSerializable::class);
        $data->expects('jsonSerialize')->once()->andReturn([]);

        $this->assertEquals($expected, $normalizer->normalize($data));
    }
}
