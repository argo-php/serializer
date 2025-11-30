<?php

declare(strict_types=1);

namespace Argo\Serializer\ContextFiller;

use Argo\EntityDefinition\Collection\AttributeCollection;
use Argo\Serializer\Context\ContextBag;

/**
 * @api
 */
interface AttributeContextFillerInterface
{
    public function fillContext(
        ContextBag $contextBag,
        AttributeCollection $attributeCollection,
        ?ContextOperationEnum $operation = null,
    ): ContextBag;
}
