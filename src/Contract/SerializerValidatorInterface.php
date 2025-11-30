<?php

declare(strict_types=1);

namespace Argo\Serializer\Contract;

use Argo\EntityDefinition\Collection\AttributeCollection;
use Argo\Serializer\Context\Internal\PathContext;

interface SerializerValidatorInterface
{
    public function validate(mixed $value, AttributeCollection $attributeCollection, PathContext $pathContext): void;
}
