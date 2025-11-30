<?php

declare(strict_types=1);

namespace Argo\Serializer\Attribute;

use Argo\Serializer\ContextFiller\ContextOperationEnum;
use Argo\Serializer\Context\ArgumentContext;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Contract\ContextAttributeInterface;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
readonly class XmlArray implements ContextAttributeInterface
{
    public function __construct(
        private ?string $itemPath = null,
    ) {}

    public function setContext(ContextBag $contextBag, ?ContextOperationEnum $operation = null): ContextBag
    {
        $argumentContext = $contextBag->get(ArgumentContext::class)->setMutateToArray(true);
        if ($this->itemPath !== null) {
            $argumentContext = $argumentContext->setNormalizedPath(explode('.', $this->itemPath));
        }

        return $contextBag->with($argumentContext);
    }
}
