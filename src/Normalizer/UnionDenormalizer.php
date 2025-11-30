<?php

declare(strict_types=1);

namespace Argo\Serializer\Normalizer;

use Argo\Serializer\Aware\DenormalizerAwareTrait;
use Argo\Serializer\Context\AttributesContext;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Context\Internal\PathContext;
use Argo\Serializer\Contract\DenormalizerAwareInterface;
use Argo\Serializer\Contract\DenormalizerInterface;
use Argo\Serializer\Contract\DiscriminatorResolverInterface;
use Argo\Serializer\Exception\InvalidArgumentTypeException;
use Argo\Serializer\Exception\ValidationException;
use Argo\Types\Complex\UnionType;
use Argo\Types\TypeInterface;

/**
 * @api
 */
class UnionDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    public function __construct(
        private readonly DiscriminatorResolverInterface $discriminatorResolver,
    ) {}

    /**
     * @psalm-param TypeInterface|UnionType $type
     * @throws ValidationException
     * @throws InvalidArgumentTypeException
     */
    public function denormalize(
        mixed         $data,
        TypeInterface $type,
        ?string       $format = null,
        ContextBag    $contextBag = new ContextBag(),
    ): mixed {
        if (!$type instanceof UnionType) {
            throw new InvalidArgumentTypeException($type, new UnionType());
        }

        $discriminatedType = $this->discriminatorResolver->resolve(
            $type,
            $data,
            $this->getDenormalizer(),
            $contextBag->get(AttributesContext::class)->attributeCollection,
            $contextBag->get(PathContext::class),
        );

        return $this->getDenormalizer()->denormalize($data, $discriminatedType, $format, $contextBag);
    }

    public function supportsDenormalization(
        mixed         $data,
        TypeInterface $type,
        ?string       $format = null,
        ContextBag    $contextBag = new ContextBag(),
    ): bool {
        return $type instanceof UnionType;
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
