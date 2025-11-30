<?php

declare(strict_types=1);

namespace Argo\Serializer\Normalizer;

use Argo\Serializer\Aware\DenormalizerAwareTrait;
use Argo\Serializer\Aware\NormalizerAwareTrait;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Context\Internal\PathContext;
use Argo\Serializer\Contract\DenormalizableInterface;
use Argo\Serializer\Contract\DenormalizerAwareInterface;
use Argo\Serializer\Contract\DenormalizerInterface;
use Argo\Serializer\Contract\NormalizableInterface;
use Argo\Serializer\Contract\NormalizerAwareInterface;
use Argo\Serializer\Contract\NormalizerInterface;
use Argo\Serializer\Exception\DenormalizationException;
use Argo\Serializer\Exception\InvalidArgumentException;
use Argo\Serializer\Exception\SerializerException;
use Argo\Types\Atomic\ClassType;
use Argo\Types\TypeInterface;

/**
 * @api
 */
class CustomNormalizer implements
    NormalizerInterface,
    DenormalizerInterface,
    NormalizerAwareInterface,
    DenormalizerAwareInterface
{
    use NormalizerAwareTrait;
    use DenormalizerAwareTrait;

    /**
     * @throws InvalidArgumentException
     */
    public function normalize(
        mixed      $data,
        ?string    $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): array|string|int|float|bool|object|null {
        if (!$data instanceof NormalizableInterface) {
            throw new InvalidArgumentException(
                sprintf('The object must implements "%s".', NormalizableInterface::class),
            );
        }

        return $data->normalize($this->getNormalizer(), $format, $contextBag);
    }

    /**
     * @psalm-param TypeInterface|ClassType<DenormalizableInterface> $type
     *
     * @throws DenormalizationException
     * @throws SerializerException
     *
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function denormalize(
        mixed         $data,
        TypeInterface $type,
        ?string       $format = null,
        ContextBag    $contextBag = new ContextBag(),
    ): DenormalizableInterface {
        if (!$type instanceof ClassType || !is_subclass_of($type->className, DenormalizableInterface::class)) {
            throw new InvalidArgumentException(
                sprintf('The object must implements "%s".', DenormalizableInterface::class),
            );
        }

        try {
            $reflection = new \ReflectionClass($type->className);

            $object = $reflection->newInstanceWithoutConstructor();
            $object->denormalize($this->getDenormalizer(), $data, $format, $contextBag);

            return $object;
        } catch (SerializerException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new DenormalizationException(
                'Error while custom denormalization',
                $contextBag->get(PathContext::class),
                $e,
            );
        }
    }

    public function supportsNormalization(mixed $data, ?string $format = null, ContextBag $contextBag = new ContextBag()): bool
    {
        return $data instanceof NormalizableInterface;
    }

    public function supportsDenormalization(
        mixed         $data,
        TypeInterface $type,
        ?string       $format = null,
        ContextBag    $contextBag = new ContextBag(),
    ): bool {
        return $type instanceof ClassType && is_subclass_of($type->className, DenormalizableInterface::class);
    }

    public function supportsDenormalizationData(
        mixed $data,
        TypeInterface $type,
        ?string $format = null,
        ContextBag $contextBag = new ContextBag(),
    ): bool {
        return true;
    }
}
