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
readonly class SerializedPath implements ContextAttributeInterface
{
    public function __construct(
        private string $path,
    ) {}

    public function setContext(ContextBag $contextBag, ?ContextOperationEnum $operation = null): ContextBag
    {
        return $contextBag->with(
            $contextBag->get(ArgumentContext::class)->setNormalizedPath(explode('.', $this->path)),
        );
    }
}
