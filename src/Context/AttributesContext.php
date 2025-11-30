<?php

declare(strict_types=1);

namespace Argo\Serializer\Context;

use Argo\EntityDefinition\Collection\AttributeCollection;
use Argo\Serializer\Contract\ContextInterface;

/**
 * @api
 */
readonly class AttributesContext implements ContextInterface
{
    public function __construct(
        public AttributeCollection $attributeCollection = new AttributeCollection(),
    ) {}
}
