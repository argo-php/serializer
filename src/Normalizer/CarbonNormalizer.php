<?php

declare(strict_types=1);

namespace Argo\Serializer\Normalizer;

use Argo\Serializer\Context\CarbonContext;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Context\Internal\PathContext;
use Argo\Serializer\Contract\DenormalizerInterface;
use Argo\Serializer\Contract\NormalizerInterface;
use Argo\Serializer\Exception\InvalidArgumentTypeException;
use Argo\Serializer\Exception\InvalidDataTypeException;
use Argo\Serializer\Exception\Validation\IncorrectTypeException;
use Argo\Serializer\Exception\Validation\UnexpectedValueFormatException;
use Argo\Types\Atomic\ClassType;
use Argo\Types\Complex\UnionType;
use Argo\Types\TypeInterface;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Carbon\Exceptions\InvalidFormatException;

/**
 * @api
 */
class CarbonNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @throws InvalidDataTypeException
     */
    public function normalize(mixed $data, ?string $format = null, ContextBag $contextBag = new ContextBag()): string
    {
        if (!$data instanceof Carbon && !$data instanceof CarbonImmutable && !$data instanceof CarbonInterval) {
            throw new InvalidDataTypeException(
                $data,
                new UnionType(
                    new ClassType(Carbon::class),
                    new ClassType(CarbonImmutable::class),
                    new ClassType(CarbonInterval::class),
                ),
            );
        }

        $carbonContext = $contextBag->get(CarbonContext::class);

        if ($data instanceof Carbon || $data instanceof CarbonImmutable) {
            $format = $carbonContext->format;
        } else {
            $format = $carbonContext->intervalFormat;
        }

        return $data->format($format);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, ContextBag $contextBag = new ContextBag()): bool
    {
        return $data instanceof Carbon || $data instanceof CarbonImmutable || $data instanceof CarbonInterval;
    }

    /**
     * @psalm-param TypeInterface|ClassType<Carbon|CarbonImmutable|CarbonInterval> $type
     *
     * @throws InvalidArgumentTypeException
     * @throws IncorrectTypeException
     * @throws UnexpectedValueFormatException
     *
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function denormalize(
        mixed         $data,
        TypeInterface $type,
        ?string       $format = null,
        ContextBag    $contextBag = new ContextBag(),
    ): Carbon|CarbonImmutable|CarbonInterval {
        if (!$type instanceof ClassType) {
            throw new InvalidArgumentTypeException(
                $type,
                new UnionType(
                    new ClassType(Carbon::class),
                    new ClassType(CarbonImmutable::class),
                    new ClassType(CarbonInterval::class),
                ),
            );
        }

        if (!$this->supportsDenormalizationData($data, $type, $format, $contextBag)) {
            throw new IncorrectTypeException(
                $contextBag->get(PathContext::class),
                'string|int',
                get_debug_type($data),
            );
        }

        $carbonContext = $contextBag->get(CarbonContext::class);

        try {
            if ($type->className === Carbon::class) {
                if (is_string($data)) {
                    return Carbon::parse($data, $carbonContext->timezone);
                } else {
                    return Carbon::createFromTimestamp($data, $carbonContext->timezone);
                }
            } elseif ($type->className === CarbonImmutable::class) {
                if (is_string($data)) {
                    return CarbonImmutable::parse($data, $carbonContext->timezone);
                } else {
                    return CarbonImmutable::createFromTimestamp($data, $carbonContext->timezone);
                }
            } elseif ($type->className === CarbonInterval::class) {
                if (is_string($data)) {
                    return CarbonInterval::parseFromLocale($data, $carbonContext->intervalLocale);
                } else {
                    return CarbonInterval::seconds($data);
                }
            }
        } catch (InvalidFormatException) {
            throw new UnexpectedValueFormatException($contextBag->get(PathContext::class), 'datetime');
        }

        throw new InvalidArgumentTypeException(
            $type,
            new UnionType(
                new ClassType(Carbon::class),
                new ClassType(CarbonImmutable::class),
                new ClassType(CarbonInterval::class),
            ),
        );
    }

    public function supportsDenormalization(
        mixed         $data,
        TypeInterface $type,
        ?string       $format = null,
        ContextBag    $contextBag = new ContextBag(),
    ): bool {
        return $type instanceof ClassType
            && (
                $type->className === Carbon::class
                || $type->className === CarbonImmutable::class
                || $type->className === CarbonInterval::class
            );
    }

    public function supportsDenormalizationData(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): bool {
        return is_int($data) || is_string($data);
    }
}
