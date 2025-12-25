<?php

declare(strict_types=1);

namespace Argo\Serializer\Normalizer;

use Argo\EntityDefinition\TypeReflector\VariableTypeReflectorInterface;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Contract\DenormalizerInterface;
use Argo\Serializer\Exception\InvalidArgumentException;
use Argo\Types\Alias\IntConstType;
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
            $type instanceof ObjectType => $this->isCastableToObject($actualType, $contextBag),
            $type instanceof IntType => $this->isCastableToInt($actualType, $contextBag),
            $type instanceof FloatType => $this->isCastableToFloat($actualType, $contextBag),
            $type instanceof BoolType => $this->isCastableToBool($actualType, $contextBag),
            $type instanceof StringType => $this->isCastableToString($actualType, $contextBag),
            $type instanceof NullType => $actualType instanceof NullType,
            $type instanceof MixedType => true,
            default => false,
        };
    }

    private function isCastableToObject(TypeInterface $type, ContextBag $contextBag): bool
    {
        return $type instanceof ArrayType || $type instanceof ObjectType;
    }

    private function isCastableToInt(TypeInterface $type, ContextBag $contextBag): bool
    {
        return $type instanceof IntType;
    }

    private function isCastableToFloat(TypeInterface $type, ContextBag $contextBag): bool
    {
        return $type instanceof FloatType || $type instanceof IntType;
    }

    private function isCastableToBool(TypeInterface $type, ContextBag $contextBag): bool
    {
        return $type instanceof BoolType
            || ($type instanceof IntConstType && ($type->value === 0 || $type->value === 1));
    }

    private function isCastableToString(TypeInterface $type, ContextBag $contextBag): bool
    {
        return $type instanceof IntType
            || $type instanceof FloatType
            || $type instanceof StringType;
    }
}
