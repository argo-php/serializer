<?php

declare(strict_types=1);

namespace Argo\Serializer\Attribute;

use Argo\EntityDefinition\Collection\AttributeCollection;
use Argo\Serializer\ContextFiller\ContextOperationEnum;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Contract\ContextAttributeInterface;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER)]
readonly class NormalizationContext implements ContextAttributeInterface
{
    private AttributeCollection $attributes;

    public function __construct(
        ContextAttributeInterface ...$attributes,
    ) {
        $this->attributes = new AttributeCollection($attributes);
    }

    public function setContext(ContextBag $contextBag, ?ContextOperationEnum $operation = null): ContextBag
    {
        if ($operation === ContextOperationEnum::Normalization) {
            $attributes = $this->attributes->getByType(ContextAttributeInterface::class);
            foreach ($attributes as $attribute) {
                $contextBag = $attribute->setContext($contextBag, $operation);
            }
        }

        return $contextBag;
    }
}
