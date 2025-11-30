<?php

declare(strict_types=1);

namespace Argo\Serializer\ContextFiller;

use Argo\EntityDefinition\Collection\AttributeCollection;
use Argo\Serializer\Context\ContextBag;

interface VariableContextFillerInterface
{
    public function getContextBag(
        string $variableName,
        AttributeCollection $attributes,
        ContextOperationEnum $operation,
        ContextBag $contextBag = new ContextBag(),
    ): ContextBag;
}
