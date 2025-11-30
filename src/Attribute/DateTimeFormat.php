<?php

declare(strict_types=1);

namespace Argo\Serializer\Attribute;

use Argo\Serializer\Context\CarbonContext;
use Argo\Serializer\Context\ContextBag;
use Argo\Serializer\Contract\ContextAttributeInterface;
use Argo\Serializer\ContextFiller\ContextOperationEnum;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
readonly class DateTimeFormat implements ContextAttributeInterface
{
    public function __construct(
        private string $format,
        private ?string $timezone = null,
    ) {}

    public function setContext(ContextBag $contextBag, ?ContextOperationEnum $operation = null): ContextBag
    {
        $carbonContext = $contextBag->get(CarbonContext::class)->setFormat($this->format);

        if ($this->timezone !== null) {
            $carbonContext = $carbonContext->setTimezone($this->timezone);
        }

        return $contextBag->with($carbonContext);
    }
}
