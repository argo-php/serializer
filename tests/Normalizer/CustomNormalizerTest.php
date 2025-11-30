<?php

declare(strict_types=1);

namespace ArgoTest\Serializer\Normalizer;

use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Contract\DenormalizableInterface;
use Argo\Serializer\Contract\DenormalizerInterface;
use Argo\Serializer\Contract\NormalizableInterface;
use Argo\Serializer\Contract\NormalizerInterface;
use Argo\Serializer\Normalizer\CustomNormalizer;
use Argo\Types\Atomic\ClassType;
use Argo\Types\Atomic\IntType;
use ArgoTest\Serializer\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CustomNormalizer::class)]
class CustomNormalizerTest extends TestCase
{
    public function testNormalize()
    {
        $mockNormalizer = \Mockery::mock(NormalizerInterface::class);
        $normalizer = new CustomNormalizer();
        $normalizer->setNormalizer($mockNormalizer);

        $actual = $normalizer->normalize(new NormalizableStub());
        $this->assertEquals(['foo' => 'bar'], $actual);
    }

    public function testDenormalize()
    {
        $mockNormalizer = \Mockery::mock(DenormalizerInterface::class);
        $normalizer = new CustomNormalizer();
        $normalizer->setDenormalizer($mockNormalizer);

        $actual = $normalizer->denormalize(
            ['foo' => 'value1', 'bar' => 'value2'],
            new ClassType(DenormalizableStub::class),
        );
        $this->assertEquals(new DenormalizableStub('value1', 'value2'), $actual);
    }

    public function testSupportsNormalization()
    {
        $normalizer = new CustomNormalizer();
        $this->assertTrue($normalizer->supportsNormalization(new NormalizableStub()));
        $this->assertFalse($normalizer->supportsNormalization(\Mockery::mock()));
    }

    public function testSupportsDenormalization()
    {
        $normalizer = new CustomNormalizer();
        $this->assertFalse($normalizer->supportsDenormalization('mixed', new IntType()));
        $this->assertFalse($normalizer->supportsDenormalization('mixed', new ClassType(\Mockery::mock()::class)));
        $this->assertTrue($normalizer->supportsDenormalization('mixed', new ClassType(DenormalizableStub::class)));
    }
}

class NormalizableStub implements NormalizableInterface
{
    public function normalize(
        NormalizerInterface $normalizer,
        ?string             $format = null,
        ContextBag          $contextBag = new ContextBag(),
    ): array|string|int|float|bool|object|null {
        return ['foo' => 'bar'];
    }
}

class DenormalizableStub implements DenormalizableInterface
{
    public function __construct(
        public string $foo,
        public string $bar,
    ) {}

    public function denormalize(
        DenormalizerInterface $denormalizer,
        mixed                 $data,
        ?string               $format = null,
        ContextBag            $contextBag = new ContextBag(),
    ): void {
        $this->foo = $data['foo'];
        $this->bar = $data['bar'];
    }
}
