<?php

declare(strict_types=1);

namespace Argo\Serializer\Contract;

use Argo\EntityDefinition\Collection\AttributeCollection;

/**
 * @api
 */
interface DiscriminatorEnricherInterface
{
    public function enrich(object $object, array $normalizedObject, AttributeCollection $attributeCollection = new AttributeCollection()): array;
}
