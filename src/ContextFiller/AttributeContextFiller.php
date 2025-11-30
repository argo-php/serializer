<?php

declare(strict_types=1);

namespace Argo\Serializer\ContextFiller;

use Argo\EntityDefinition\Collection\AttributeCollection;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Contract\ContextAttributeInterface;

/**
 * @api
 */
class AttributeContextFiller implements AttributeContextFillerInterface
{
    public function fillContext(
        ContextBag $contextBag,
        AttributeCollection $attributeCollection,
        ?ContextOperationEnum $operation = null,
    ): ContextBag {
        $contextAttributes = $attributeCollection->getByType(ContextAttributeInterface::class);

        foreach ($contextAttributes as $contextAttribute) {
            $contextBag = $contextAttribute->setContext($contextBag, $operation);
        }

        return $contextBag;
    }
}
