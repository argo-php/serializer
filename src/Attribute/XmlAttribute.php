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
readonly class XmlAttribute implements ContextAttributeInterface
{
    public function __construct(
        private ?string $name = null,
    ) {}

    public function setContext(ContextBag $contextBag, ?ContextOperationEnum $operation = null): ContextBag
    {
        $argumentContext = $contextBag->get(ArgumentContext::class);
        if ($this->name === null) {
            $argumentContext = $argumentContext->setNormalizedPath(['@' . $argumentContext->name]);
        } else {
            $argumentContext = $argumentContext->setNormalizedPath(['@' . $this->name]);
        }

        return $contextBag->with($argumentContext);
    }
}
