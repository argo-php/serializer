<?php

declare(strict_types=1);

namespace ArgoTest\Serializer\Normalizer;

use Argo\Serializer\Context\CarbonContext;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Exception\Validation\IncorrectTypeException;
use Argo\Serializer\Exception\Validation\UnexpectedValueFormatException;
use Argo\Serializer\Normalizer\CarbonNormalizer;
use Argo\Types\Atomic\ClassType;
use Argo\Types\Atomic\IntType;
use ArgoTest\Serializer\TestCase;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CarbonNormalizer::class)]
class CarbonNormalizerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        date_default_timezone_set('UTC');
    }

    public function testNormalize()
    {
        $normalizer = new CarbonNormalizer();
        $this->assertSame('2024-02-02T12:23:45+00:00', $normalizer->normalize(Carbon::parse('2024-02-02 12:23:45')));
        $this->assertSame(
            '2024-02-02T12:23:45+00:00',
            $normalizer->normalize(CarbonImmutable::parse('2024-02-02 12:23:45')),
        );
        $this->assertSame('P0Y0M0DT2H20M0S', $normalizer->normalize(CarbonInterval::fromString('2 hours 20 minutes')));

        $context = new ContextBag(
            new CarbonContext(format: 'd.m.Y H:i', intervalFormat: '%h hours %i minutes'),
        );
        $this->assertSame(
            '02.02.2024 12:23',
            $normalizer->normalize(Carbon::parse('2024-02-02 12:23:45'), contextBag: $context),
        );
        $this->assertSame(
            '02.02.2024 12:23',
            $normalizer->normalize(CarbonImmutable::parse('2024-02-02 12:23:45'), contextBag: $context),
        );
        $this->assertSame(
            '2 hours 20 minutes',
            $normalizer->normalize(CarbonInterval::fromString('P2H20M'), contextBag: $context),
        );
    }

    public function testDenormalizeCarbon()
    {
        $expectedString = '2024-02-02 12:23:45';
        $expectedInt = 2234322334;

        $normalizer = new CarbonNormalizer();

        $actual = $normalizer->denormalize($expectedString, new ClassType(Carbon::class));
        $this->assertEquals(Carbon::parse($expectedString), $actual);

        $actual = $normalizer->denormalize($expectedString, new ClassType(CarbonImmutable::class));
        $this->assertEquals(CarbonImmutable::parse($expectedString), $actual);

        $actual = $normalizer->denormalize($expectedInt, new ClassType(Carbon::class));
        $this->assertEquals(Carbon::createFromTimestamp($expectedInt), $actual);

        $actual = $normalizer->denormalize($expectedInt, new ClassType(CarbonImmutable::class));
        $this->assertEquals(CarbonImmutable::createFromTimestamp($expectedInt), $actual);
    }

    public function testDenormalizeCarbonWithTimezone()
    {
        $expectedString = '2024-02-02 12:23:45';
        $expectedInt = 2234322334;
        $expectedTimezone = 'Europe/Amsterdam';

        $normalizer = new CarbonNormalizer();

        $context = new ContextBag(
            new CarbonContext(timezone: $expectedTimezone),
        );

        $actual = $normalizer->denormalize($expectedString, new ClassType(Carbon::class), contextBag: $context);
        $this->assertEquals(Carbon::parse($expectedString, $expectedTimezone), $actual);

        $actual = $normalizer->denormalize($expectedString, new ClassType(CarbonImmutable::class), contextBag: $context);
        $this->assertEquals(CarbonImmutable::parse($expectedString, $expectedTimezone), $actual);

        $actual = $normalizer->denormalize($expectedInt, new ClassType(Carbon::class), contextBag: $context);
        $this->assertEquals(Carbon::createFromTimestamp($expectedInt, $expectedTimezone), $actual);

        $actual = $normalizer->denormalize($expectedInt, new ClassType(CarbonImmutable::class), contextBag: $context);
        $this->assertEquals(CarbonImmutable::createFromTimestamp($expectedInt, $expectedTimezone), $actual);
    }

    public function testDenormalizeCarbonInterval()
    {
        $expectedString = '2 hours 20 minutes';
        $expectedRuString = '2 часа 20 минут';
        $expectedInt = 223432;
        $extendedLocale = 'ru';

        $normalizer = new CarbonNormalizer();

        $actual = $normalizer->denormalize($expectedString, new ClassType(CarbonInterval::class));
        $this->assertEquals(CarbonInterval::fromString($expectedString), $actual);

        $context = new ContextBag(
            new CarbonContext(intervalLocale: $extendedLocale),
        );

        $actual = $normalizer->denormalize($expectedRuString, new ClassType(CarbonInterval::class), contextBag: $context);
        $this->assertEquals(CarbonInterval::parseFromLocale($expectedRuString, $extendedLocale), $actual);

        $actual = $normalizer->denormalize($expectedInt, new ClassType(CarbonInterval::class));
        $this->assertEquals(CarbonInterval::seconds($expectedInt), $actual);
    }

    public function testDenormalizeIncorrectTypeException()
    {
        $normalizer = new CarbonNormalizer();

        $this->expectException(IncorrectTypeException::class);
        $normalizer->denormalize([], new ClassType(Carbon::class));
    }

    public function testDenormalizeUnexpectedValueFormatException()
    {
        $normalizer = new CarbonNormalizer();

        $this->expectException(UnexpectedValueFormatException::class);
        $normalizer->denormalize('random', new ClassType(Carbon::class));
    }

    public function testSupportsNormalization()
    {
        $normalizer = new CarbonNormalizer();
        $this->assertTrue($normalizer->supportsNormalization(new Carbon()));
        $this->assertTrue($normalizer->supportsNormalization(new CarbonImmutable()));
        $this->assertTrue($normalizer->supportsNormalization(new CarbonInterval()));
        $this->assertFalse($normalizer->supportsNormalization('dsfds'));
    }

    public function testSupportsDenormalization()
    {
        $normalizer = new CarbonNormalizer();
        $this->assertTrue($normalizer->supportsDenormalization('mixed', new ClassType(Carbon::class)));
        $this->assertTrue($normalizer->supportsDenormalization('mixed', new ClassType(CarbonInterval::class)));
        $this->assertTrue($normalizer->supportsDenormalization('mixed', new ClassType(CarbonImmutable::class)));
        $this->assertFalse($normalizer->supportsDenormalization('mixed', new ClassType(\Mockery::mock()::class)));
        $this->assertFalse($normalizer->supportsDenormalization('mixed', new IntType()));
    }
}
