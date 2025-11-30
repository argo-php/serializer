<?php

declare(strict_types=1);

namespace Argo\Serializer\Contract;

use Argo\Serializer\ContextFiller\ContextOperationEnum;
use Argo\Serializer\Context\ContextBag;

interface ContextAttributeInterface
{
    public function setContext(ContextBag $contextBag, ?ContextOperationEnum $operation = null): ContextBag;
}
