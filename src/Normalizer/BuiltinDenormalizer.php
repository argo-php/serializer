<?php

declare(strict_types=1);

namespace Argo\Serializer\Normalizer;

use Argo\EntityDefinition\TypeReflector\VariableTypeReflectorInterface;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Contract\DenormalizerInterface;
use Argo\Serializer\Exception\InvalidArgumentException;
use Argo\Types\Atomic\ArrayType;
use Argo\Types\Atomic\BoolType;
use Argo\Types\Atomic\FloatType;
use Argo\Types\Atomic\IntType;
use Argo\Types\Atomic\MixedType;
use Argo\Types\Atomic\NullType;
use Argo\Types\Atomic\ObjectType;
use Argo\Types\Atomic\StringType;
use Argo\Types\TypeInterface;

/**
 * @api
 */
class BuiltinDenormalizer implements DenormalizerInterface
{
    public function __construct(
        private readonly VariableTypeReflectorInterface $variableTypeReflector,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @psalm-suppress InvalidReturnStatement
     */
    public function denormalize(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): mixed {
        return match (true) {
            $type instanceof FloatType => (float) $data,
            $type instanceof StringType => (string) $data,
            $type instanceof ObjectType => (object) $data,
            $type instanceof BoolType => (bool) $data,
            $type instanceof IntType => (int) $data,
            $type instanceof MixedType,
            $type instanceof NullType => $data,
            default => throw new InvalidArgumentException(
                sprintf('The property must have builtin type, actual: [%s]', $type),
            ),
        };
    }

    public function supportsDenormalization(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): bool {
        return $type instanceof ObjectType
            || $type instanceof StringType
            || $type instanceof MixedType
            || $type instanceof NullType
            || $type instanceof FloatType
            || $type instanceof BoolType
            || $type instanceof IntType;
    }

    public function supportsDenormalizationData(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): bool {
        if (!$this->supportsDenormalization($data, $type, $format, $contextBag)) {
            return false;
        }
        $actualType = $this->variableTypeReflector->getVariableType($data);

        return match (true) {
            $type instanceof ObjectType => $actualType instanceof ArrayType || $actualType instanceof ObjectType,
            $type instanceof IntType => $actualType instanceof IntType,
            $type instanceof FloatType => $actualType instanceof FloatType || $actualType instanceof IntType,
            $type instanceof BoolType => $actualType instanceof BoolType,
            $type instanceof StringType => $actualType instanceof IntType || $actualType instanceof FloatType || $actualType instanceof StringType,
            $type instanceof NullType => $actualType instanceof NullType,
            $type instanceof MixedType => true,
            default => false,
        };
    }
}
