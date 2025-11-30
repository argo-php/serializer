<?php

declare(strict_types=1);

namespace Argo\Serializer\Normalizer;

use Argo\Serializer\Aware\DenormalizerAwareTrait;
use Argo\Serializer\Aware\NormalizerAwareTrait;
use Argo\Serializer\Context\AttributesContext;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Context\Internal\PathContext;
use Argo\Serializer\Context\SerializationContext;
use Argo\Serializer\Contract\DenormalizerAwareInterface;
use Argo\Serializer\Contract\DenormalizerInterface;
use Argo\Serializer\Contract\NormalizerAwareInterface;
use Argo\Serializer\Contract\NormalizerInterface;
use Argo\Serializer\Exception\InvalidArgumentTypeException;
use Argo\Serializer\Exception\InvalidDataTypeException;
use Argo\Serializer\Exception\Validation\IncorrectTypeException;
use Argo\Serializer\Exception\ValidationBagException;
use Argo\Serializer\Exception\ValidationException;
use Argo\Types\Atomic\ArrayType;
use Argo\Types\TypeInterface;

/**
 * @api
 */
class ArrayNormalizer implements
    NormalizerInterface,
    NormalizerAwareInterface,
    DenormalizerInterface,
    DenormalizerAwareInterface
{
    use NormalizerAwareTrait;
    use DenormalizerAwareTrait;

    /**
     * @throws InvalidDataTypeException
     * @throws ValidationBagException
     */
    public function normalize(
        mixed $data,
        string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): array {
        if (!is_iterable($data)) {
            throw new InvalidDataTypeException($data, new ArrayType());
        }

        $result = [];
        $errorsBag = new ValidationBagException();
        $pathContext = $contextBag->get(PathContext::class);

        foreach ($data as $key => $value) {
            try {
                $result[$key] = $this->getNormalizer()->normalize(
                    $value,
                    $format,
                    $contextBag->with($pathContext->item($key)),
                );
            } catch (ValidationException|ValidationBagException $exception) {
                if ($contextBag->get(SerializationContext::class)->stopOnFirstValidationError) {
                    throw $exception;
                }

                $errorsBag->addException($exception);
            }
        }

        if (!$errorsBag->empty()) {
            throw $errorsBag;
        }

        return $result;
    }

    public function supportsNormalization(mixed $data, string $format = null, ContextBag $contextBag = new ContextBag()): bool
    {
        return is_iterable($data);
    }

    /**
     * @throws InvalidArgumentTypeException
     * @throws IncorrectTypeException
     * @throws ValidationBagException
     *
     * @psalm-suppress RedundantConditionGivenDocblockType
     */
    public function denormalize(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): array {
        if (!$type instanceof ArrayType) {
            throw new InvalidArgumentTypeException($type, new ArrayType());
        }

        $pathContext = $contextBag->get(PathContext::class);
        if (!$this->supportsDenormalizationData($data, $type)) {
            throw new IncorrectTypeException($pathContext, 'array|object', get_debug_type($data));
        }
        if (is_object($data)) {
            $data = (array) $data;
        }

        $result = [];
        $errorsBag = new ValidationBagException();

        foreach ($data as $key => $value) {
            try {
                $result[$key] = $this->getDenormalizer()->denormalize(
                    $value,
                    $type->valueType,
                    $format,
                    $contextBag->with(
                        $pathContext->item($key),
                        new AttributesContext(),
                    ),
                );
            } catch (ValidationException|ValidationBagException $exception) {
                if ($contextBag->get(SerializationContext::class)->stopOnFirstValidationError) {
                    throw $exception;
                }

                $errorsBag->addException($exception);
            }
        }

        if (!$errorsBag->empty()) {
            throw $errorsBag;
        }

        return $result;
    }

    public function supportsDenormalization(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): bool {
        return $type instanceof ArrayType;
    }

    public function supportsDenormalizationData(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): bool {
        return is_array($data) || is_object($data);
    }
}
