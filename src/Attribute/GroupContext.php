<?php

declare(strict_types=1);

namespace Argo\Serializer\Attribute;

use Argo\EntityDefinition\Collection\AttributeCollection;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Context\SerializationContext;
use Argo\Serializer\ContextFiller\ContextOperationEnum;
use Argo\Serializer\Contract\ContextAttributeInterface;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
readonly class GroupContext implements ContextAttributeInterface
{
    private AttributeCollection $attributes;

    public function __construct(
        private ?string $group = null,
        ContextAttributeInterface ...$attributes,
    ) {
        $this->attributes = new AttributeCollection($attributes);
        ;
    }

    public function setContext(ContextBag $contextBag, ?ContextOperationEnum $operation = null): ContextBag
    {
        $group = $contextBag->get(SerializationContext::class)->group;

        if ($group !== $this->group) {
            return $contextBag;
        }

        $attributes = $this->attributes->getByType(ContextAttributeInterface::class);
        foreach ($attributes as $attribute) {
            $contextBag = $attribute->setContext($contextBag, $operation);
        }

        return $contextBag;
    }
}
