<?php

declare(strict_types=1);

namespace Argo\Serializer\Contract;

use Argo\EntityDefinition\Collection\AttributeCollection;
use Argo\Serializer\Context\Internal\PathContext;
use Argo\Serializer\Exception\ValidationException;
use Argo\Types\NamedTypeInterface;
use Argo\Types\TypeInterface;

/**
 * @api
 */
interface DiscriminatorResolverInterface
{
    /**
     * @throws ValidationException
     */
    public function resolve(
        TypeInterface $targetType,
        mixed $value,
        DenormalizerInterface $denormalizer,
        AttributeCollection $attributeCollection = new AttributeCollection(),
        PathContext $pathContext = new PathContext(),
    ): NamedTypeInterface;
}
