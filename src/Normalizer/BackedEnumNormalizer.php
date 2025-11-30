<?php

declare(strict_types=1);

namespace Argo\Serializer\Normalizer;

use Argo\Serializer\Context\BackedEnumContext;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Context\Internal\PathContext;
use Argo\Serializer\Contract\DenormalizerInterface;
use Argo\Serializer\Contract\NormalizerInterface;
use Argo\Serializer\Exception\InvalidArgumentException;
use Argo\Serializer\Exception\InvalidArgumentTypeException;
use Argo\Serializer\Exception\InvalidDataTypeException;
use Argo\Serializer\Exception\Validation\IncorrectTypeException;
use Argo\Serializer\Exception\Validation\UnexpectedEnumValueException;
use Argo\Types\Atomic\ClassType;
use Argo\Types\TypeInterface;

/**
 * @api
 */
class BackedEnumNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @throws InvalidDataTypeException
     */
    public function normalize(
        mixed $data,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): int|string {
        if (!$data instanceof \BackedEnum) {
            throw new InvalidDataTypeException($data, new ClassType(\BackedEnum::class));
        }

        return $data->value;
    }

    public function supportsNormalization(
        mixed $data,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): bool {
        return $data instanceof \BackedEnum;
    }

    /**
     * @psalm-param TypeInterface|ClassType<\BackedEnum> $type
     *
     * @throws UnexpectedEnumValueException
     * @throws IncorrectTypeException
     * @throws InvalidArgumentException
     * @throws InvalidArgumentTypeException
     *
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function denormalize(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): ?\BackedEnum {
        if (!$type instanceof ClassType || !is_subclass_of($type->className, \BackedEnum::class)) {
            throw new InvalidArgumentTypeException($type, new ClassType(\BackedEnum::class));
        }

        $pathContext = $contextBag->get(PathContext::class);
        $enumContext = $contextBag->get(BackedEnumContext::class);

        $enumName = $type->className;
        $cases = $enumName::cases();
        if (count($cases) === 0) {
            throw new InvalidArgumentException('Enumeration must have at least one backed enumeration.');
        }

        if (is_int($cases[0]->value) && is_numeric($data)) {
            $data = (int) $data;
        }
        if (is_string($cases[0]->value) && is_int($data)) {
            $data = (string) $data;
        }

        if ($enumContext->allowInvalidValue) {
            if ((!is_int($data) && !is_string($data))) {
                return $enumContext->defaultWhenInvalidValue;
            }

            try {
                return $enumName::tryFrom($data);
            } catch (\TypeError) {
                return $enumContext->defaultWhenInvalidValue;
            }
        }

        if (!is_int($data) && !is_string($data)) {
            throw new IncorrectTypeException($pathContext, 'int|string', get_debug_type($data));
        }

        try {
            return $enumName::from($data);
        } catch (\ValueError) {
            throw new UnexpectedEnumValueException($pathContext, $enumName);
        }
    }

    public function supportsDenormalization(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): bool {
        return $type instanceof ClassType && is_subclass_of($type->className, \BackedEnum::class);
    }

    public function supportsDenormalizationData(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): bool {
        return is_string($data) || is_int($data);
    }
}
