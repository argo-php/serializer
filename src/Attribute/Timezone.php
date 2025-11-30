<?php

declare(strict_types=1);

namespace Argo\Serializer\Attribute;

use Argo\Serializer\ContextFiller\ContextOperationEnum;
use Argo\Serializer\Context\CarbonContext;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Contract\ContextAttributeInterface;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
readonly class Timezone implements ContextAttributeInterface
{
    public function __construct(
        private string $timezone,
    ) {}

    public function setContext(ContextBag $contextBag, ?ContextOperationEnum $operation = null): ContextBag
    {
        return $contextBag->with(
            $contextBag->get(CarbonContext::class)->setTimezone($this->timezone),
        );
    }
}
