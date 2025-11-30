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
readonly class DateIntervalFormat implements ContextAttributeInterface
{
    public function __construct(
        private string $format,
        private string|false|null $locale = false,
    ) {}

    public function setContext(ContextBag $contextBag, ?ContextOperationEnum $operation = null): ContextBag
    {
        $carbonContext = $contextBag->get(CarbonContext::class)->setIntervalFormat($this->format);

        if ($this->locale !== false) {
            $carbonContext = $carbonContext->setIntervalLocale($this->locale);
        }

        return $contextBag->with($carbonContext);
    }
}
