<?php

declare(strict_types=1);

namespace ArgoTest\Serializer\Normalizer;

use Argo\Serializer\Contract\DenormalizerInterface;
use Argo\Serializer\Contract\NormalizerInterface;
use Argo\Serializer\Normalizer\ArrayNormalizer;
use Argo\Types\Atomic\ArrayType;
use Argo\Types\Atomic\ClassType;
use ArgoTest\Serializer\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ArrayNormalizer::class)]
class ArrayNormalizerTest extends TestCase
{
    private ArrayNormalizer $normalizer;
    private NormalizerInterface&DenormalizerInterface $serializer;

    protected function setUp(): void
    {
        $this->serializer = \Mockery::mock(NormalizerInterface::class, DenormalizerInterface::class);
        $this->normalizer = new ArrayNormalizer();
        $this->normalizer->setNormalizer($this->serializer);
        $this->normalizer->setDenormalizer($this->serializer);
    }

    public function testNormalize()
    {
        $series = [
            [new ArrayDummy('one', 'two'), ['foo' => 'one', 'bar' => 'two']],
            [new ArrayDummy('three', 'four'), ['foo' => 'three', 'bar' => 'four']],
        ];

        $this->serializer->expects('normalize')
            ->times(2)
            ->andReturnUsing(function ($data) use (&$series) {
                [$expectedArgs, $return] = array_shift($series);
                $this->assertEquals($expectedArgs, $data);

                return $return;
            });

        $result = $this->normalizer->normalize([new ArrayDummy('one', 'two'), new ArrayDummy('three', 'four')]);

        $this->assertEquals(
            [
                ['foo' => 'one', 'bar' => 'two'],
                ['foo' => 'three', 'bar' => 'four'],
            ],
            $result,
        );
    }

    public function testDenormalize()
    {
        $series = [
            [['foo' => 'one', 'bar' => 'two'], new ArrayDummy('one', 'two')],
            [['foo' => 'three', 'bar' => 'four'], new ArrayDummy('three', 'four')],
        ];

        $this->serializer->expects('denormalize')
            ->times(2)
            ->andReturnUsing(function ($data) use (&$series) {
                [$expectedArgs, $return] = array_shift($series);
                $this->assertSame($expectedArgs, $data);

                return $return;
            });

        $result = $this->normalizer->denormalize(
            [
                ['foo' => 'one', 'bar' => 'two'],
                ['foo' => 'three', 'bar' => 'four'],
            ],
            new ArrayType(new ClassType(ArrayDummy::class)),
        );

        $this->assertEquals(
            [
                new ArrayDummy('one', 'two'),
                new ArrayDummy('three', 'four'),
            ],
            $result,
        );
    }

    public function testSupportsValidArray()
    {
        $this->assertTrue(
            $this->normalizer->supportsDenormalization(
                [
                    ['foo' => 'one', 'bar' => 'two'],
                    ['foo' => 'three', 'bar' => 'four'],
                ],
                new ArrayType(new ClassType(ArrayDummy::class)),
                'json',
            ),
        );
    }

    public function testSupportsNoArray()
    {
        $this->assertFalse(
            $this->normalizer->supportsDenormalization(
                ['foo' => 'one', 'bar' => 'two'],
                new ClassType(ArrayDummy::class),
            ),
        );
    }
}

class ArrayDummy
{
    public $foo;
    public $bar;

    public function __construct($foo, $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
}
